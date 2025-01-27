<?php
/*
 * File name: DashboardAPIController.php
 * Last modified: 2024.04.10 at 14:21:46
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Http\Controllers\API;

use App\Criteria\Bookings\BookingsOfUserCriteria;
use App\Criteria\Earnings\EarningOfUserCriteria;
use App\Criteria\EServices\EServicesOfUserCriteria;
use App\Criteria\Salons\SalonsOfUserCriteria;
use App\Http\Controllers\Controller;
use App\Repositories\BookingRepository;
use App\Repositories\EarningRepository;
use App\Repositories\EServiceRepository;
use App\Repositories\SalonRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Prettus\Repository\Exceptions\RepositoryException;

class DashboardAPIController extends Controller
{
    /** @var  BookingRepository */
    private BookingRepository $bookingRepository;

    /** @var  SalonRepository */
    private SalonRepository $salonRepository;
    /**
     * @var EServiceRepository
     */
    private EServiceRepository $eServiceRepository;
    /**
     * @var EarningRepository
     */
    private EarningRepository $earningRepository;

    public function __construct(BookingRepository $bookingRepo, EarningRepository $earningRepository, SalonRepository $salonRepo, EServiceRepository $eServiceRepository)
    {
        parent::__construct();
        $this->bookingRepository = $bookingRepo;
        $this->salonRepository = $salonRepo;
        $this->eServiceRepository = $eServiceRepository;
        $this->earningRepository = $earningRepository;
    }

    /**
     * Display a listing of the Faq.
     * GET|HEAD /provider/dashboard
     * @param Request $request
     * @return JsonResponse
     */
    public function provider(Request $request): JsonResponse
    {
        $statistics = [];
        try {

            $this->earningRepository->pushCriteria(new EarningOfUserCriteria(auth()->id()));
            $earning['description'] = 'total_earning';
            $earning['value'] = $this->earningRepository->all()->sum('salon_earning');
            $statistics[] = $earning;

            $this->bookingRepository->pushCriteria(new BookingsOfUserCriteria(auth()->id()));
            $bookingsCount['description'] = "total_bookings";
            $bookingsCount['value'] = $this->bookingRepository->all('bookings.id')->count();
            $statistics[] = $bookingsCount;

            $this->salonRepository->pushCriteria(new SalonsOfUserCriteria(auth()->id()));
            $salonsCount['description'] = "total_salons";
            $salonsCount['value'] = $this->salonRepository->all('salons.id')->count();
            $statistics[] = $salonsCount;

            $this->eServiceRepository->pushCriteria(new EServicesOfUserCriteria(auth()->id()));
            $eServicesCount['description'] = "total_e_services";
            $eServicesCount['value'] = $this->eServiceRepository->all('e_services.id')->count();
            $statistics[] = $eServicesCount;


        } catch (RepositoryException $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse($statistics, 'Statistics retrieved successfully');
    }
}
