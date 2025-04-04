<?php
/*
 * File name: UserAPIController.php
 * Last modified: 2024.04.10 at 14:47:27
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Http\Controllers\API;

use App\Criteria\Users\SalonsCustomersCriteria;
use App\Events\DoPaymentEvent;
use App\Events\SendEmailOtpEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Repositories\CustomFieldRepository;
use App\Repositories\RoleRepository;
use App\Repositories\UploadRepository;
use App\Repositories\UserRepository;
use App\Services\OtpService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InfyOm\Generator\Criteria\LimitOffsetCriteria;
use Prettus\Repository\Criteria\RequestCriteria;
use App\Services\PaymentService;
use App\Services\PartenerShipService;
use Illuminate\Support\Carbon;

class UserAPIController extends Controller
{
    private UserRepository $userRepository;
    private UploadRepository $uploadRepository;
    private RoleRepository $roleRepository;
    private CustomFieldRepository $customFieldRepository;
    
    /**
     * @var PaymentService
     */
    private PaymentService $paymentService;

      /**
     * @var PartenerShipService
     */
    private PartenerShipService $partenerShipService;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(PartenerShipService $partenerShipService ,PaymentService $paymentService  , UserRepository $userRepository, UploadRepository $uploadRepository, RoleRepository $roleRepository, CustomFieldRepository $customFieldRepo)
    {
        parent::__construct();
        $this->userRepository = $userRepository;
        $this->uploadRepository = $uploadRepository;
        $this->roleRepository = $roleRepository;
        $this->roleRepository = $roleRepository;
        $this->customFieldRepository = $customFieldRepo;
        $this->paymentService =  $paymentService ;
        $this->partenerShipService =  $partenerShipService ;

    }

    function login(Request $request): JsonResponse
    {
        try {
            
            $this->validate($request, [
                'email' => 'nullable|email',
                'phone_number' => 'nullable|max:255',
                'password' => 'required',
              
            ]);

            // Determine whether the input is an email or phone number
            if($request->has('email') && !is_null($request->input('email')) ) $loginField =  'email' ;
            if($request->has('phone_number') && !is_null($request->input('phone_number')) && $request->input('phone_number') !="" ){
                if( filter_var($request->input('phone_number'), FILTER_VALIDATE_EMAIL)){
                    $request->merge(['email' => $request->input('phone_number')]) ;
                    $loginField =  'email' ;
                } else{
                    $loginField =  'phone_number' ;
                } 
            } 
            return $this->sendError([$loginField => $request->input($loginField), 'password' => $request->input('password')],401);
            if (auth()->attempt([$loginField => $request->input($loginField), 'password' => $request->input('password')])) {
            // if (auth()->attempt(['phone_number' => $request->input('phone_number'), 'password' => $request->input('password')])) {
                // Authentication passed...   // Authentication passed...
                $user = auth()->user();
                $user->device_token = $request->input('device_token', '');
                $user->save();
                return $this->sendResponse($user->load('roles'), 'User retrieved successfully');
            } else {
                return $this->sendError(__('auth.failed'),401);
            }
        } catch (ValidationException $e) {
            return $this->sendError(array_values($e->errors()),422);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage(),400);
        }

    }

    function user(Request $request): JsonResponse
    {
        $user = $this->userRepository->findByField('api_token', $request->input('api_token'))->first();

        if (!$user) {
            return $this->sendError('User not found');
        }

        return $this->sendResponse($user->load('roles'), 'User retrieved successfully');
    }

    /**
     * Create a new user instance after a valid registration.
     * for use of User::$rules_v2 it required an suppplement
     * attribute version . if version is not given User::$rules wil be used
     * @param Request $request
     * @return JsonResponse
     */
    function register(Request $request): JsonResponse
    {
        try {
            if(!$request->has('version') ){
                $this->validate($request, User::$rules);
                $registerwith = 'phone_number' ;
            }else{
                $this->validate($request, User::$rules_v2);
                 // Determine whether the input is an email or phone number
                $registerwith = $request->has('email')?  'email' : 'phone_number' ;
            }

            $user = new User;
            $user->name = $request->input('name');
            $user->email = $request->input('email');
            $user->phone_number = $request->input('phone_number');
            $user->phone_verified_at = $request->input('phone_verified_at');
            $user->device_token = $request->input('device_token', '');
            $user->password = Hash::make($request->input('password'));
            $user->api_token = Str::random(60);
            $user->save();

            $defaultRoles = $this->roleRepository->findByField('name','customer');
            $defaultRoles = $defaultRoles->pluck('name')->toArray();
            $user->assignRole($defaultRoles);

            if($registerwith == 'email') event(new SendEmailOtpEvent($user));


            // Récupère l'ID d'affiliation à partir du paramètre de requête
            if ($request->has('code_affiliation') && $request->input('code_affiliation') != ""  ) { 
                $affiliation = $this->partenerShipService->find($request->input('code_affiliation')) ;
                
                $this->partenerShipService->proceedPartenerShip($user,$affiliation) ;
                
                // Attribue la récompense au partenaire
                $partner = $affiliation->user;
                if($partner){ 
                    // $this->paymentService->createPayment(50,setting('app_default_wallet_id'),$partner );
                    $paymentInfo = ["amount"=>setting('partener_rewards'),"payer_wallet"=>setting('app_default_wallet_id'), "user"=>$partner] ;
                    event(new DoPaymentEvent($paymentInfo));
                }

                $user->update([
                    'sponsorship' => $affiliation,
                    'sponsorship_at' => now(),
                ]);
            }

            //credité le wallet du client
            $paymentInfo = ["amount"=>setting('customer_initial_amount'),"payer_wallet"=>setting('app_default_wallet_id'), "user"=>$user] ;
            event(new DoPaymentEvent($paymentInfo));
            
        
            $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->userRepository->model());

            foreach (getCustomFieldsValues($customFields, $request) as $value) {
                $user->customFieldsValues()
                    ->updateOrCreate(['custom_field_id' => $value['custom_field_id']], $value);
            }
        } catch (ValidationException $e) {
            return $this->sendError(array_values($e->errors()),422);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }


        return $this->sendResponse($user->load('roles'), 'User retrieved successfully');
    }

    /**
     * Create a new user instance after a valid registration.
     * for use of User::$rules_v2 it required an suppplement
     * attribute version . if version is not given User::$rules wil be used
     * @param Request $request
     * @return JsonResponse
     */
    function v2_register(Request $request): JsonResponse
    {
        try {
          
            $this->validate($request, User::$rules_v2);
                // Determine whether the input is an email or phone number
            $registerwith = $request->has('email')?  'email' : 'phone_number' ;
            

            $user = new User;
            $user->name = $request->input('name');
            $user->email = $request->input('email');
            $user->phone_number = $request->input('phone_number');
            $user->phone_verified_at = $request->input('phone_verified_at');
            $user->device_token = $request->input('device_token', '');
            $user->password = Hash::make($request->input('password'));
            $user->api_token = Str::random(60);
            $user->save();

            $defaultRoles = $this->roleRepository->findByField('name','customer');
            $defaultRoles = $defaultRoles->pluck('name')->toArray();
            $user->assignRole($defaultRoles);

            if($registerwith == 'email') event(new SendEmailOtpEvent($user));


            // Récupère l'ID d'affiliation à partir du paramètre de requête
            if ($request->has('code_affiliation') && $request->input('code_affiliation') != ""  ) { 
                $affiliation = $this->partenerShipService->find($request->input('code_affiliation')) ;
                
                $this->partenerShipService->proceedPartenerShip($user,$affiliation) ;
                
                // Attribue la récompense au partenaire
                $partner = $affiliation->user;
                if($partner){ 
                    // $this->paymentService->createPayment(50,setting('app_default_wallet_id'),$partner );
                    $paymentInfo = ["amount"=>setting('partener_rewards'),"payer_wallet"=>setting('app_default_wallet_id'), "user"=>$partner] ;
                    event(new DoPaymentEvent($paymentInfo));
                }

                $user->update([
                    'sponsorship' => $affiliation,
                    'sponsorship_at' => now(),
                ]);
            }

            //credité le wallet du client
            $paymentInfo = ["amount"=>setting('customer_initial_amount'),"payer_wallet"=>setting('app_default_wallet_id'), "user"=>$user] ;
            event(new DoPaymentEvent($paymentInfo));
            
        
            $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->userRepository->model());

            foreach (getCustomFieldsValues($customFields, $request) as $value) {
                $user->customFieldsValues()
                    ->updateOrCreate(['custom_field_id' => $value['custom_field_id']], $value);
            }
        } catch (ValidationException $e) {
            return $this->sendError(array_values($e->errors()),422);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }


        return $this->sendResponse($user->load('roles'), 'User retrieved successfully');
    }

    function logout(Request $request): JsonResponse
    {
        $user = $this->userRepository->findByField('api_token', $request->input('api_token'))->first();
        if (!$user) {
            return $this->sendError('User not found');
        }
        try {
            auth()->logout();
        } catch (Exception $e) {
            $this->sendError($e->getMessage());
        }
        return $this->sendResponse($user['name'], 'User logout successfully');

    }

    function settings(Request $request): JsonResponse
    {
        $settings = setting()->all();
      
        
        if (!$settings) {
            return $this->sendError('Settings not found');
        }
        $upload = $this->uploadRepository->findByField('uuid', setting('app_logo', ''))->first();
        $settings['app_logo'] = asset('images/logo_default.png');
        if ($upload && $upload->hasMedia('app_logo')) {
            $settings['app_logo'] = $upload->getFirstMediaUrl('app_logo');
        }

        return $this->sendResponse($settings, 'Settings retrieved successfully');
    }

    /**
     * Update the specified User in storage.
     *
     * @param int $id
     * @param UpdateUserRequest $request
     * @return JsonResponse
     */
    public function update(int $id, UpdateUserRequest $request): JsonResponse
    {
        $user = $this->userRepository->findWithoutFail($id);
        if (empty($user)) {
            return $this->sendError('User not found');
        }
        $input = $request->except(['api_token']);
        try {
            if ($request->has('device_token')) {
                $user = $this->userRepository->update($request->only('device_token'), $id);
            } else {
                $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->userRepository->model());
                if (isset($input['password'])) {
                    $input['password'] = Hash::make($request->input('password'));
                }
                if (isset($input['avatar']) && $input['avatar']) {
                    $cacheUpload = $this->uploadRepository->getByUuid($input['avatar']);
                    $mediaItem = $cacheUpload->getMedia('avatar')->first();
                    if ($user->hasMedia('avatar')) {
                        $user->getFirstMedia('avatar')->delete();
                    }
                    $mediaItem->copy($user, 'avatar');
                }
                $user = $this->userRepository->update($input, $id);

                foreach (getCustomFieldsValues($customFields, $request) as $value) {
                    $user->customFieldsValues()
                        ->updateOrCreate(['custom_field_id' => $value['custom_field_id']], $value);
                }
            }
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse($user, __('lang.updated_successfully', ['operator' => __('lang.user')]));
    }

    function sendResetLinkEmail(Request $request): JsonResponse
    {
        try {
            $this->validate($request, ['email' => 'required|email|exists:users']);
            $response = Password::broker()->sendResetLink(
                $request->only('email')
            );
            if ($response == Password::RESET_LINK_SENT) {
                return $this->sendResponse(true, 'Reset link was sent successfully');
            } else {
                return $this->sendError('Reset link not sent');
            }
        } catch (ValidationException $e) {
            return $this->sendError($e->getMessage());
        } catch (Exception) {
            return $this->sendError("Email not configured in your admin panel settings");
        }
    }

    /**
     * Send otp code via email.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sendEmailVerificationOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email'
        ]);

        $user = $this->userRepository->findByField('email', $request->input('email'))->first();
        if (!$user) {
            return $this->sendError('it seems your email do not exist in our server');
        }

        (new OtpService())->generate($user) ;

        return response()->json(['message' => 'OTP envoyé pour la vérification de l\'email.']);
    }

    public function verifyEmailOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|digits:5',
        ]);

        $user = $this->userRepository->findWhere([  'email' => $request->input('email') , 
                                            'otp'=> $request->input('otp') , 
                                            'otp_expires_at' =>  ['otp_expires_at', '>', now()],
                                            ])->first() ;
    
        if (!$user) {
            return $this->sendError('OTP invalide ou expiré.');
            // return response()->json(['message' => 'OTP invalide ou expiré.'], 400);
        }

        // Mark email as verified
        $user = $this->userRepository->update(["email_verified_at" =>  Carbon::now()] , $user->id); 

        return response()->json(['message' => 'Email vérifié avec succès.']);
    }


    /**
     * Display a listing of the employees.
     * GET|HEAD /provider/employees
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function employees(Request $request): JsonResponse
    {
        try {
            $this->userRepository->pushCriteria(new RequestCriteria($request));
            $this->userRepository->pushCriteria(new SalonsCustomersCriteria());
            $this->userRepository->pushCriteria(new LimitOffsetCriteria($request));
            $users = $this->userRepository->all();
            $this->filterCollection($request, $users);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse($users->toArray(), 'Employees retrieved successfully');
    }

    /**
     * Remove the authenticated user from storage.
     *
     *
     * @return JsonResponse
     */
    public function destroy(): JsonResponse
    {
        try {
            $user = $this->userRepository->delete(auth()->id());
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendResponse($user, __('lang.deleted_successfully', ['operator' => __('lang.user')]));
    }
}
