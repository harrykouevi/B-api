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
use App\Repositories\WalletRepository;
use App\Criteria\Wallets\WalletsOfUserCriteria;
use App\Types\WalletType;
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

    /**
     * @var WalletRepository
     */
    private WalletRepository $walletRepository;

    public function __construct(BookingRepository $bookingRepo, EarningRepository $earningRepository, SalonRepository $salonRepo, EServiceRepository $eServiceRepository, WalletRepository $walletRepository)
    {
        parent::__construct();
        $this->bookingRepository = $bookingRepo;
        $this->salonRepository = $salonRepo;
        $this->eServiceRepository = $eServiceRepository;
        $this->earningRepository = $earningRepository;
        $this->walletRepository = $walletRepository;
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

            // Récupérer le solde du wallet PRINCIPAL (Igris) du salon
            $this->walletRepository->pushCriteria(new WalletsOfUserCriteria(auth()->id()));
            $wallets = $this->walletRepository->all();

            // Trouver le wallet PRINCIPAL
            $principalWallet = $wallets->firstWhere('wallet_type', WalletType::PRINCIPAL->value);

            $earning['description'] = 'total_earning';
            $earning['value'] = $principalWallet ? $principalWallet->balance : 0;
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
