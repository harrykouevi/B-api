<?php
/*
 * File name: HomeController.php
 * Last modified: 2024.04.10 at 13:21:42
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Http\Controllers;

use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return View
     */
    public function index():View
    {
        return view('home');
    }
}
