<?php
/*
 * File name: RestrictIp.php
 * Last modified: 2024.04.18 at 17:50:01
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RestrictIp
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        try{
            $ipsDeny = setting('blocked_ips',[]);
            if(count($ipsDeny) >= 1 )
            {
                if(in_array(request()->ip(), $ipsDeny))
                {
                    return response()->view('vendor.errors.page', ['code'=>403,'message' => "Unauthorized access, IP address was <b>".request()->ip()."</b>"]);
                }
            }
        } catch (\Exception $exception) {


        }
        return $next($request);
    }
}
