<?php
/*
 * File name: DashboardController.php
 * Last modified: 2024.04.10 at 14:21:46
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Http\Controllers;

use App\Repositories\BookingRepository;
use App\Repositories\EarningRepository;
use App\Repositories\SalonRepository;
use App\Repositories\UserRepository;
use Illuminate\View\View;

class DashboardController extends Controller
{

    /** @var  BookingRepository */
    private BookingRepository $bookingRepository;


    /**
     * @var UserRepository
     */
    private UserRepository $userRepository;

    /** @var  SalonRepository */
    private SalonRepository $salonRepository;
    /** @var  EarningRepository */
    private EarningRepository $earningRepository;

    public function __construct(BookingRepository $bookingRepo, UserRepository $userRepo, EarningRepository $earningRepository, SalonRepository $salonRepo)
    {
        parent::__construct();
        $this->bookingRepository = $bookingRepo;
        $this->userRepository = $userRepo;
        $this->salonRepository = $salonRepo;
        $this->earningRepository = $earningRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return View
     */
    public function index():View
    {
        $bookingsCount = $this->bookingRepository->count();
        $membersCount = $this->userRepository->count();
        $salonsCount = $this->salonRepository->count();
        $salons = $this->salonRepository->orderBy('id', 'desc')->limit(4);
        $earning = $this->earningRepository->all()->sum('total_earning');
        $ajaxEarningUrl = env('API_URL') . '/api/payments/byMonth?api_token=' . auth()->user()->api_token;
        // $ajaxEarningUrl = route('payments.byMonth', ['api_token' => auth()->user()->api_token]);
        return view('dashboard.index')
            ->with("ajaxEarningUrl", $ajaxEarningUrl)
            ->with("bookingsCount", $bookingsCount)
            ->with("salonsCount", $salonsCount)
            ->with("salons", $salons)
            ->with("membersCount", $membersCount)
            ->with("earning", $earning);
    }
}
