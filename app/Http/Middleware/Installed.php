<?php
/*
 * File name: Installed.php
 * Last modified: 2024.04.18 at 17:35:01
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class Installed
{

    private array $exceptNames = [
        'LaravelInstaller*',
        'LaravelUpdater*',
        'debugbar*'
    ];

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $installed = File::exists(storage_path('installed'));
        if ($this->match($request->route()) || $installed) {
            return $next($request);
        }
        return redirect(url('install'));

    }

    private function match(Route $route): bool
    {
        foreach ($this->exceptNames as $except) {
            if (Str::is($except, $route->getName())) {
                return true;
            }
        }
        return false;
    }
}
