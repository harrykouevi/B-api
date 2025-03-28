<?php
/*
 * File name: TranslationAPIController.php
 * Last modified: 2024.04.10 at 14:47:27
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TranslationAPIController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    function supportedLocales(Request $request): JsonResponse
    {
        try {
            if (($request->segment(2) == 'salon_owner')) {
                $file = "salon_owner_app.json";
            } else {
                $file = "customer_app.json";
            }
            $dir = base_path("resources/lang/");
            $locales = array_diff(scandir($dir), array('..', '.'));
            $supportedLocales = [];
            foreach ($locales as $locale) {
                if (file_exists(base_path("resources/lang/$locale/$file"))) {
                    $supportedLocales[] = $locale;
                }
            }
        } catch (Exception $exception) {
            return $this->sendError($exception->getMessage());
        }
        return $this->sendResponse($supportedLocales, 'Supported Locales retrieved successfully');
    }

    function translations(Request $request): JsonResponse
    {
        try {
            $this->validate($request, [
                'locale' => 'required|string:10',
            ]);
            if (($request->segment(2) == 'salon_owner')) {
                $file = "salon_owner_app.json";
            } else {
                $file = "customer_app.json";
            }
            $locale = $request->get('locale', 'en');
            $translation = json_decode(
                file_get_contents(base_path("resources/lang/$locale/$file")), true
            );
        } catch (ValidationException|Exception) {
            return $this->sendError("Translation Not Found");
        }
        return $this->sendResponse($translation, 'Translation retrieved successfully');
    }
}
