<?php
/*
 * File name: UserAPIController.php
 * Last modified: 2024.04.10 at 14:47:28
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Http\Controllers\API\SalonOwner;

use App\Criteria\Users\SalonsCustomersCriteria;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Repositories\CustomFieldRepository;
use App\Repositories\RoleRepository;
use App\Repositories\UploadRepository;
use App\Repositories\UserRepository;
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


class UserAPIController extends Controller
{
    /**
     * @var UserRepository
     */
    private UserRepository $userRepository;

    private UploadRepository $uploadRepository;
    private RoleRepository $roleRepository;
    private CustomFieldRepository $customFieldRepository;

    /**
     * @var PaymentService
     */
    private PaymentService $paymentService;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(PaymentService $paymentService ,UserRepository $userRepository, UploadRepository $uploadRepository, RoleRepository $roleRepository, CustomFieldRepository $customFieldRepo)
    {
        parent::__construct();
        $this->userRepository = $userRepository;
        $this->uploadRepository = $uploadRepository;
        $this->roleRepository = $roleRepository;
        $this->customFieldRepository = $customFieldRepo;
        $this->paymentService =  $paymentService ;

    }

    function login(Request $request): JsonResponse
    {
        try {
            $this->validate($request, [
                'email' => 'nullable|email',
                'phone_number' => 'required|max:255',
                'password' => 'required',
            ]);
            if (auth()->attempt(['phone_number' => $request->input('phone_number'), 'password' => $request->input('password')])) {
                // Authentication passed...
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
            return $this->sendError($e->getMessage(),401);
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
     *
     * @param Request $request
     * @return JsonResponse
     */
    function register(Request $request): JsonResponse
    {
      
        try {
            $this->validate($request, User::$rules);
            $user = new User;
            $user->name = $request->input('name');
            $user->email = $request->input('email');
            $user->phone_number = $request->input('phone_number');
            $user->phone_verified_at = $request->input('phone_verified_at');
            $user->device_token = $request->input('device_token', '');
            $user->password = Hash::make($request->input('password'));
            $user->api_token = Str::random(60);
            $user->save();

            $defaultRoles = $this->roleRepository->findByField('name', 'salon owner');
            $defaultRoles = $defaultRoles->pluck('name')->toArray();
            $user->assignRole($defaultRoles);
          
            $payment = $this->paymentService->createPayment($user,setting('owner_initial_amount'));

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
        // $settings = array_intersect_key($settings,
        //     [
        //         'default_tax' => '',
        //         'default_currency' => '',
        //         'default_currency_decimal_digits' => '',
        //         'app_name' => '',
        //         'salon_app_name' => '',
        //         'currency_right' => '',
        //         'enable_paypal' => '',
        //         'enable_stripe' => '',
        //         'enable_razorpay' => '',
        //         'main_color' => '',
        //         'main_dark_color' => '',
        //         'second_color' => '',
        //         'second_dark_color' => '',
        //         'accent_color' => '',
        //         'accent_dark_color' => '',
        //         'scaffold_dark_color' => '',
        //         'scaffold_color' => '',
        //         'google_maps_key' => '',
        //         'fcm_key' => '',
        //         'mobile_language' => '',
        //         'app_version' => '',
        //         'enable_version' => '',
        //         'distance_unit' => '',
        //         'default_theme' => '',
        //         'default_country_code' => '',
        //         'enable_otp' => ''
        //     ]
        // );

        if (!$settings) {
            return $this->sendError('Settings not found');
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
     * Display a listing of the employees.
     * GET|HEAD /salon_owner/employees
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
}
