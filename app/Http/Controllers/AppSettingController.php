<?php

/*
 * File name: AppSettingController.php
 * Last modified: 2024.04.10 at 11:47:05
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Http\Controllers;

use App\Repositories\CurrencyRepository;
use App\Repositories\RoleRepository;
use App\Repositories\UploadRepository;
use App\Repositories\UserRepository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Laracasts\Flash\Flash;
use RachidLaasri\LaravelInstaller\Helpers\MigrationsHelper;
use Session;
use Themsaid\Langman\Manager;

class AppSettingController extends Controller
{
    use MigrationsHelper;

    /** @var  UserRepository */
    private UserRepository $userRepository;

    /**
     * @var RoleRepository
     */
    private RoleRepository $roleRepository;

    private Manager $langManager;
    private UploadRepository $uploadRepository;
    private CurrencyRepository $currencyRepository;

    public function __construct(UserRepository $userRepo, RoleRepository $roleRepo, UploadRepository $uploadRepository, CurrencyRepository $currencyRepository)
    {
        parent::__construct();
        $this->userRepository = $userRepo;
        $this->roleRepository = $roleRepo;
        $this->currencyRepository = $currencyRepository;
        $this->langManager = new Manager(new Filesystem(), config('langman.path'), []);
        $this->uploadRepository = $uploadRepository;
    }

    public function update(Request $request): RedirectResponse
    {
        if (!config('installer.demo_app')) {
            $input = $request->except(['_method', '_token']);
            if (Str::endsWith(url()->previous(), "app/globals")) {
                if (empty($input['app_logo'])) {
                    unset($input['app_logo']);
                }
                if (empty($input['custom_field_models'])) {
                    setting()->forget('custom_field_models');
                }
                if (!isset($input['blocked_ips'])) {
                    unset($input['blocked_ips']);
                    setting()->forget('blocked_ips');
                }
            } else if (Str::contains(url()->previous(), "payment")) {
                if (isset($input['default_currency'])) {
                    $currency = $this->currencyRepository->findWithoutFail($input['default_currency']);
                    if (!empty($currency)) {
                        $input['default_currency_id'] = $input['default_currency'];
                        $input['default_currency'] = $currency->symbol;
                        $input['default_currency_code'] = $currency->code;
                        $input['default_currency_decimal_digits'] = $currency->decimal_digits;
                        $input['default_currency_rounding'] = $currency->rounding;
                    }
                }
//                if(isset($input['enable_stripe']) && $input['enable_stripe'] == 1){
//                    $input['enable_razorpay'] = 0;
//                }
//                if(isset($input['enable_razorpay']) && $input['enable_razorpay'] == 1){
//                    $input['enable_stripe'] = 0;
//                }
            }
            if (empty($input['mail_password'])) {
                unset($input['mail_password']);
            }
            if ($request->hasFile('firebase_credentials')) {
                try {
                    $request->file('firebase_credentials')->storeAs('fcm', 'firebase_credentials.json');
                } catch (\Exception $e) {
                }
            }
            $input = array_map(function ($value) {
                return is_null($value) ? false : $value;
            }, $input);

            setting($input)->save();
            Flash::success(__('lang.updated_successfully', ['operator' => __('lang.app_setting_global')]));
            Artisan::call("config:clear");
        } else {
            Flash::warning('This is only demo app you can\'t change this section ');
        }

        return redirect()->back();
    }

    public function updateLanguage(Request $request): RedirectResponse
    {
        $locale = $request->get('locale');
        Session::put('locale', $locale);
        Flash::success(__('lang.updated_successfully', ['operator' => __('lang.app_setting_global')]));
        Artisan::call("config:clear");
        return redirect()->back();
    }

    public function syncTranslation(Request $request): RedirectResponse
    {
        if (!config('installer.demo_app')) {
            Artisan::call('langman:sync');
        } else {
            Flash::warning('This is only demo app you can\'t change this section ');
        }
        return redirect()->back();
    }

    public function checkForUpdates(Request $request): RedirectResponse
    {
        if (!config('installer.demo_app')) {
            Artisan::call('config:clear');
            Artisan::call('cache:clear');
            Artisan::call('cache:forget', ['key' => 'spatie.permission.cache']);
            Artisan::call('view:clear');
            Artisan::call('route:clear');
            $executedMigrations = $this->getExecutedMigrations();
            $newMigrations = $this->getMigrations(config('installer.currentVersion', 'v100'));

            $containsUpdate = !empty($newMigrations) && count(array_intersect($newMigrations, $executedMigrations->toArray())) == count($newMigrations);
            if (!$containsUpdate) {
                return redirect(url('update/' . config('installer.currentVersion', 'v100')));
            }
        }
        Flash::warning(__('lang.app_setting_already_updated'));
        return redirect()->back();

    }

    /**
     * Clear cache for the application
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function clearCache(Request $request): RedirectResponse
    {
        if (!config('installer.demo_app')) {
            Artisan::call('cache:forget', ['key' => 'spatie.permission.cache']);
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('view:clear');
            Artisan::call('route:clear');
            Flash::success(__('lang.app_setting_cache_is_cleared'));
        } else {
            Flash::warning('This is only demo app you can\'t change this section ');
        }
        return redirect()->back();
    }

    /**
     * Translate the given key
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function translate(Request $request): RedirectResponse
    {
        //translate only lang.php file

        if (!config('installer.demo_app')) {
            $inputs = $request->except(['_method', '_token', '_current_lang']);
            $currentLang = $request->input('_current_lang');
            if (!$inputs) {
                Flash::error('Translate not loaded');
                return redirect()->back();
            }
            $langFiles = $this->langManager->files();
            $langFiles = array_filter($langFiles, function ($v, $k) {
                return $k == 'lang';
            }, ARRAY_FILTER_USE_BOTH);

            if (!$langFiles) {
                Flash::error('Translate not loaded');
                return redirect()->back();
            }
            foreach ($langFiles as $filename => $items) {
                $path = $items[$currentLang];
                $needed = [];
                foreach ($inputs as $key => $input) {
                    if (Str::startsWith($key, $filename)) {
                        $langKeyWithoutFile = explode('|', $key, 2)[1];
                        $needed = array_merge_recursive($needed, getNeededArray('|', $langKeyWithoutFile, $input));
                    }
                }
                ksort($needed);
                $this->langManager->writeFile($path, $needed);
            }
        } else {
            Flash::warning('This is only demo app you can\'t change this section ');
        }

        return redirect()->back();
    }


    public function index($type = null, $tab = null): View|RedirectResponse
    {
        if (empty($type)) {
            Flash::error(trans('lang.app_setting_global') . 'not found');
            return redirect()->back();
        }
        $executedMigrations = $this->getExecutedMigrations();
        $newMigrations = $this->getMigrations(config('installer.currentVersion', 'v100'));
        $containsUpdate = !empty($newMigrations) && count(array_intersect($newMigrations, $executedMigrations->toArray())) != count($newMigrations);

        $langFiles = [];
        $timezones = [];
        $languages = getAvailableLanguages();
        $mobileLanguages = getLanguages();
        $countries = getCountries();
        if ($type === 'translation' && $tab) {
            if (!Arr::has($languages, $tab)) {
                Flash::error('Translate not loaded');
                return redirect()->back();
            }
            $langFiles = $this->langManager->files();
            return view('settings.' . $type . '.index', compact(['languages', 'type', 'tab', 'langFiles']));

        }

        foreach (timezone_abbreviations_list() as $abbr => $timezone) {
            foreach ($timezone as $val) {
                if (isset($val['timezone_id'])) {
                    $group = preg_split('/\//', $val['timezone_id'])[0];
                    $timezones[$group][$val['timezone_id']] = $val['timezone_id'];
                }
            }
        }
        $upload = $this->uploadRepository->findByField('uuid', setting('app_logo'))->first();

        $currencies = $this->currencyRepository->all()->pluck('name_symbol', 'id');

        $customFieldModels = getModelsClasses(app_path('Models'));

        return view('settings.' . $type . '.' . $tab, compact(['languages', 'type', 'tab', 'langFiles', 'timezones', 'upload', 'customFieldModels', 'currencies', 'mobileLanguages', 'countries', 'containsUpdate']));
    }

    /**
     * Initialize Firebase
     * @return Response
     */
    public function initFirebase(): Response
    {
        return response()->view('vendor.notifications.sw_firebase')->header('Content-Type', 'application/javascript');
    }


}
