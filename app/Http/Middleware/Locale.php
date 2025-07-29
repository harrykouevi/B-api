<?php namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

class Locale
{

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        try {
            // Début modifié -------------------------------------------------
            $defaultLocale = config('app.locale', 'en'); // Récupérer APP_LOCALE
            $locale = $defaultLocale; // Priorité à la config

            if (Session::has('locale')) {
                $locale = Session::get('locale');
            } else {
                $headerLocale = $this->parseHttpLocale($request);
                if (!empty($headerLocale)) {
                    $locale = $headerLocale;
                }
            }
            // Fin modification ----------------------------------------------

            app()->setLocale($locale);
            Carbon::setLocale($locale);
        } catch (Exception $exception) {
        }

        return $next($request);
    }


    private function parseHttpLocale(Request $request): string
    {
        $list = explode(',', $request->header('Accept-Language'));

        $locales = Collection::make($list)
            ->map(function ($locale) {
                $parts = explode(';', $locale);

                $mapping['locale'] = trim($parts[0]);

                if (isset($parts[1])) {
                    $factorParts = explode('=', $parts[1]);

                    $mapping['factor'] = $factorParts[1];
                } else {
                    $mapping['factor'] = 1;
                }

                return $mapping;
            })
            ->sortByDesc(function ($locale) {
                return $locale['factor'];
            });
        $locale = $locales->first()['locale'];
        if (str_contains($locale, '-')) {
            $locale = explode('-', $locale)[0];
        }
        if (str_contains($locale, '_')) {
            $locale = explode('_', $locale)[0];
        }
        return $locale;
    }

}
/*
 * File name: Locale.php
 * Last modified: 2024.04.18 at 17:22:50
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */



