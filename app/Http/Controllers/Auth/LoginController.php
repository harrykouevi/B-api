<?php
/*
 * File name: LoginController.php
 * Last modified: 2024.04.10 at 14:47:27
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Repositories\RoleRepository;
use App\Repositories\UploadRepository;
use App\Repositories\UserRepository;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Prettus\Validator\Exceptions\ValidatorException;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected string $redirectTo = '/';
    private UserRepository $userRepository;
    private UploadRepository $uploadRepository;
    private RoleRepository $roleRepository;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(UserRepository $userRepository, UploadRepository $uploadRepository, RoleRepository $roleRepository)
    {
        parent::__construct();
        $this->middleware('guest')->except('logout');
        $this->userRepository = $userRepository;
        $this->uploadRepository = $uploadRepository;
        $this->roleRepository = $roleRepository;
    }

    /**
     * Redirect the user to the Facebook authentication page.
     * @param $service
     * @return RedirectResponse
     */
    public function redirectToProvider($service): RedirectResponse
    {
        return Socialite::driver($service)->redirect();
    }

    /**
     * Obtain the user information from Facebook.
     *
     * @param $service
     * @return RedirectResponse
     */
    /** @noinspection PhpUndefinedFieldInspection */
    public function handleProviderCallback($service): RedirectResponse
    {
        $userSocial = Socialite::driver($service)->user();
        $user = User::where('email', $userSocial->email)->first();
        if (!$user) {
            $user = new User;
            $user->name = $userSocial->name;
            $user->email = $userSocial->email;
            $user->password = bcrypt(Str::random());
            $user->save();
            $defaultRoles = $this->roleRepository->findByField('default', '1');
            $defaultRoles = $defaultRoles->pluck('name')->toArray();
            $user->assignRole($defaultRoles);

            try {
                $upload = $this->uploadRepository->create(['uuid' => $userSocial->token]);
                $upload->addMediaFromUrl($userSocial->avatar_original)
                    ->withCustomProperties(['uuid' => $userSocial->token])
                    ->toMediaCollection('avatar');

                $cacheUpload = $this->uploadRepository->getByUuid($userSocial->token);
                $mediaItem = $cacheUpload->getMedia('avatar')->first();
                $mediaItem->copy($user, 'avatar');
            } catch (ValidatorException $e) {
                \Flash::error($e->getMessage());
            }
        }
        auth()->login($user, true);
        return redirect(route('users.profile'));
    }
}
