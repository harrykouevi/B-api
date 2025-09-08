<?php
/*
 * File name: BookingAPIController.php
 * Last modified: 2024.04.10 at 13:21:42
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Http\Controllers\API;


use Exception;
use App\Models\User;
use App\Models\Address;
use App\Models\Booking;
use Illuminate\Http\Request;
use App\Notifications\NewBooking;
use Illuminate\Http\JsonResponse;
use App\Events\BookingChangedEvent;

use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Repositories\SalonRepository;
use App\Repositories\CouponRepository;
use App\Repositories\OptionRepository;
use App\Services\BookingReportService;
use App\Criteria\Coupons\ValidCriteria;
use App\Repositories\AddressRepository;
use App\Repositories\BookingRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\EServiceRepository;
use App\Events\BookingStatusChangedEvent;
use App\Services\BookingCancellationService;
use Illuminate\Support\Facades\Notification;
use App\Repositories\BookingStatusRepository;
use Illuminate\Validation\ValidationException;
use Prettus\Repository\Criteria\RequestCriteria;
use App\Criteria\Bookings\BookingsOfUserCriteria;
use App\Models\Tax;
use InfyOm\Generator\Criteria\LimitOffsetCriteria;
use Prettus\Validator\Exceptions\ValidatorException;
use Prettus\Repository\Exceptions\RepositoryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Class BookingController
 * @package App\Http\Controllers\API
 */
class BookingAPIController extends Controller
{
    /** @var  BookingRepository */
    private BookingRepository $bookingRepository;

    /**
     * @var BookingStatusRepository
     */
    private BookingStatusRepository $bookingStatusRepository;
    /**
     * @var PaymentRepository
     */
    private PaymentRepository $paymentRepository;
    /**
     * @var AddressRepository
     */
    private AddressRepository $addressRepository;
    /**
     * @var EServiceRepository
     */
    private EServiceRepository $eServiceRepository;
    /**
     * @var SalonRepository
     */
    private SalonRepository $salonRepository;
    /**
     * @var CouponRepository
     */
    private CouponRepository $couponRepository;
    /**
     * @var OptionRepository
     */
    private OptionRepository $optionRepository;

    private BookingReportService $reportService;

    private BookingCancellationService $cancellationService;


    public function __construct(BookingRepository $bookingRepo
        , BookingStatusRepository                 $bookingStatusRepo, PaymentRepository $paymentRepo, AddressRepository $addressRepository, EServiceRepository $eServiceRepository, SalonRepository $salonRepository, CouponRepository $couponRepository, OptionRepository $optionRepository)
    {
        parent::__construct();
        $this->bookingRepository = $bookingRepo;
        $this->bookingStatusRepository = $bookingStatusRepo;
        $this->paymentRepository = $paymentRepo;
        $this->addressRepository = $addressRepository;
        $this->eServiceRepository = $eServiceRepository;
        $this->salonRepository = $salonRepository;
        $this->couponRepository = $couponRepository;
        $this->optionRepository = $optionRepository;
        $this->reportService = app(BookingReportService::class);
        $this->cancellationService = app(BookingCancellationService::class);
    }

    /**
     * Display a listing of the Booking.
     * GET|HEAD /bookings
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $this->bookingRepository->pushCriteria(new RequestCriteria($request));
            $this->bookingRepository->pushCriteria(new BookingsOfUserCriteria(auth()->id()));
            $this->bookingRepository->pushCriteria(new LimitOffsetCriteria($request));
        } catch (RepositoryException $e) {
            return $this->sendError($e->getMessage());
        }
        $bookings = $this->bookingRepository->all();
        $this->filterCollection($request, $bookings);
        return $this->sendResponse($bookings->toArray(), 'Bookings retrieved successfully');
    }

    /**
     * Display the specified Booking.
     * GET|HEAD /bookings/{id}
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function show(int $id, Request $request): JsonResponse
    {
        try {
            $this->bookingRepository->pushCriteria(new RequestCriteria($request));
            $this->bookingRepository->pushCriteria(new LimitOffsetCriteria($request));
        } catch (RepositoryException $e) {
            return $this->sendError($e->getMessage());
        }
        $booking = $this->bookingRepository->findWithoutFail($id);
        if (empty($booking)) {
            return $this->sendError('Booking not found');
        }
        $this->filterModel($request, $booking);
        return $this->sendResponse($booking->toArray(), 'Booking retrieved successfully');


    }

    /**
     * Store a newly created Booking in storage.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $input = $request->all();
            $salon = $this->salonRepository->find($input['salon_id']);
            if (isset($input['address'])) {
                $this->validate($request, [
                    'address.address' => Address::$rules['address'],
                    'address.longitude' => Address::$rules['longitude'],
                    'address.latitude' => Address::$rules['latitude'],
                ]);
                $address = $this->addressRepository->updateOrCreate(['address' => $input['address']['address']], $input['address']);
                if (empty($address)) {
                    return $this->sendError(__('lang.not_found', ['operator', __('lang.address')]));
                } else {
                    $input['address'] = $address;
                }
            } else {
                $input['address'] = $salon->address;
            }
            if (isset($input['e_services'])) {
                $input['e_services'] = $this->eServiceRepository->findWhereIn('id', $input['e_services']);
                // coupon code
                if (isset($input['code'])) {
                    $this->couponRepository->pushCriteria(new ValidCriteria($request));
                    $coupon = $this->couponRepository->first();
                    $input['coupon'] = $coupon->getValue($input['e_services']);
                }
            }
            $taxes = $salon->taxes;
            $input['salon'] = $salon;
            $input['taxes'] = $taxes;

            if (isset($input['options'])) {
                $input['options'] = $this->optionRepository->findWhereIn('id', $input['options']);
            }
            $input['booking_status_id'] = $this->bookingStatusRepository->find(1)->id;

            $booking = $this->bookingRepository->create($input);
            
        } catch (ValidationException $e) {
           
            return $this->sendError(array_values($e->errors()),422);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage() , 500);
        }

        try {
            // Log des IDs utilisateurs pour vérification sans risquer les références circulaires
            Log::debug('Notifying salon users', [
                'salon_id' => $salon->id,
                'user_ids' => $salon->users->pluck('id')->toArray()
            ]);
            Log::info('Notification sent to salon users', [
                'salon_id' => $salon->id,
                'users_count' => $salon->users->count(),
                'booking_id' => $booking->id
            ]);

            // Envoi de la notification avec les données essentielles
            Notification::send(
                $salon->users,
                new NewBooking($booking->setRelations([]))
            );
        } catch (Exception $e) {
            Log::error('Notification error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString() // Ajout de la stack trace pour le débogage
            ]);
        }

        return $this->sendResponse($booking->toArray(), __('lang.saved_successfully', ['operator' => __('lang.booking')]));
    }

    /**
     * Update the specified Booking in storage.
     *
     * @param int $id
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function update(int $id, Request $request): JsonResponse
    {
        $input = $request->all();
        if (in_array($input['booking_status_id'], [ 8, 9])) {
            return $this->sendError(__('Cette route  ne peut pas gerer cette demande de modification. Utiliser la route'));
        }

        $oldBooking = $this->bookingRepository->findWithoutFail($id);
        if (empty($oldBooking)) {
            return $this->sendError('Booking not found');
        }
        
        try {
            if (isset($input['cancel']) && $input['cancel'] == '1') {
                $input['payment_status_id'] = 3;
                $input['booking_status_id'] = 7;
            }

            

            // si il y a commission
            if(array_key_exists('taxes', $input))
            {
                //autres données recu du mobile
                // montant_a_reverser
                // commission_calculee
                $input["purchase_taxes"] = $input['taxes'] ;
                unset($input['taxes']);  
            }
            $booking = $this->bookingRepository->update($input, $id);
            
            if (isset($input['payment_status_id'])) {
        
                event(new BookingChangedEvent($booking)); 
            }

            if (isset($input['booking_status_id']) && $input['booking_status_id'] != $oldBooking->booking_status_id) {
                
                event(new BookingStatusChangedEvent($booking));

            }

        } catch (ValidatorException $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse($booking->toArray(), __('lang.saved_successfully', ['operator' => __('lang.booking')]));
    }

    /**
     *  REPORT BOOKINGS
     */

     public function report(int $id, Request $request): JsonResponse
    {
        try {
            $this->validate($request, [
                'booking_at' => 'required|date|after:now',
                'start_at' => 'nullable|date',
                'ends_at' => 'nullable|date|after:start_at',
                'reason' => 'nullable|string|max:500'
            ]);

            $newBookingData = $request->only(['booking_at', 'start_at', 'ends_at']);
            $reason = $request->input('reason', 'Report demandé par le client');

            $result = $this->reportService->reportBooking($id, $newBookingData, $reason);

            return $this->sendResponse($result, 'Rendez-vous reporté avec succès');

        } catch (ValidationException $e) {
            return $this->sendError($e->errors(), 422);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage(), 400);
        }
    }

    public function reportHistory(int $id): JsonResponse
    {
        try {
            $history = $this->reportService->getReportHistory($id);
            
            return $this->sendResponse([
                'history' => $history,
                'total_reports' => count($history) - 1,
            ], 'Historique récupéré avec succès');
            
        } catch (Exception $e) {
            return $this->sendError($e->getMessage(), 400);
        }
    }

    public function canReport(int $id): JsonResponse
    {
        try {
            $booking = $this->bookingRepository->findWithoutFail($id);
            
            if (empty($booking)) {
                return $this->sendError('Rendez-vous introuvable', 404);
            }

            $canReport = $booking->canBeReported();
            $reason = $canReport ? null : $this->getCannotReportReason($booking);

            return $this->sendResponse([
                'can_report' => $canReport,
                'reason' => $reason,
                'booking_status' => $booking->bookingStatus->status ?? 'Unknown'
            ], 'Vérification effectuée');

        } catch (Exception $e) {
            return $this->sendError($e->getMessage(), 400);
        }
    }

    private function getCannotReportReason(Booking $booking): string
    {
        if ($booking->cancel) {
            return 'Le rendez-vous est déjà annulé';
        }
        
        if ($booking->booking_status_id === 6) {
            return 'Le rendez-vous est déjà terminé';
        }
        
        if ($booking->booking_status_id === 7) {
            return 'Le rendez-vous a échoué';
        }
        
        if ($booking->booking_status_id === 9) {
            return 'Le rendez-vous a déjà été reporté';
        }
        
        if ($booking->booking_at <= now()) {
            return 'Le rendez-vous est dans le passé';
        }
        
        return 'Conditions non remplies pour le report';
    }

    /**
     *  Cancel BOOKINGS
     */

    public function cancel(int $id, Request $request): JsonResponse
    {
        try {
            // VALIDATION DES DONNÉES
            $this->validate($request, [
                'cancellation_reason' => 'required|string|min:10|max:500',
                'cancelled_by' => 'sometimes|string|in:customer,salon_owner,admin'
            ]);

            $reason = $request->input('cancellation_reason');
            $cancelledBy = $request->input('cancelled_by', 'customer');
            
            // VÉRIFICATION DES PERMISSIONS
            $booking = $this->bookingRepository->findWithoutFail($id);
            if (empty($booking)) {
                return $this->sendError('Rendez-vous introuvable', 404);
            }

            $userRoles = auth()->user()->getRoleNames()->toArray();
            
            if (!$this->cancellationService->canUserCancelBooking($booking, auth()->id(), $userRoles)) {
                return $this->sendError('Vous n\'avez pas l\'autorisation d\'annuler ce rendez-vous', 403);
            }

            // EXÉCUTION DE L'ANNULATION
            $result = $this->cancellationService->cancelBooking($id, $reason, $cancelledBy);

            return $this->sendResponse($result, 'Rendez-vous annulé avec succès');

        } catch (ValidationException $e) {
            return $this->sendError($e->errors(), 422);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage(), 400);
        }
    }
    /**
     *  GET CANCELLATIONS HISTORY
     */
    public function myCancellations(): JsonResponse
    {
        try {
            $history = $this->cancellationService->getUserCancellationHistory(auth()->id());
            
            return $this->sendResponse([
                'cancellations' => $history,
                'total_cancelled' => count($history),
            ], 'Historique récupéré avec succès');
            
        } catch (Exception $e) {
            return $this->sendError($e->getMessage(), 400);
        }
    }

    public function canCancel(int $id): JsonResponse
    {
        try {
            $booking = $this->bookingRepository->findWithoutFail($id);
            
            if (empty($booking)) {
                return $this->sendError('Rendez-vous introuvable', 404);
            }

            $canCancel = $booking->canBeCancelled();
            $userRoles = auth()->user()->getRoleNames()->toArray();
            $hasPermission = $this->cancellationService->canUserCancelBooking($booking, auth()->id(), $userRoles);
            
            $reason = null;
            if (!$canCancel) {
                $reason = $this->getCannotCancelReason($booking);
            } elseif (!$hasPermission) {
                $reason = 'Vous n\'avez pas l\'autorisation d\'annuler ce rendez-vous';
            }

            return $this->sendResponse([
                'can_cancel' => $canCancel && $hasPermission,
                'reason' => $reason,
                'booking_status' => $booking->bookingStatus->status ?? 'Unknown',
                'refund_amount' => $canCancel ? $this->calculatePotentialRefund($booking) : 0
            ], 'Vérification effectuée');

        } catch (Exception $e) {
            return $this->sendError($e->getMessage(), 400);
        }
    }

    private function getCannotCancelReason(Booking $booking): string
    {
        if ($booking->cancel) {
            return 'Le rendez-vous est déjà annulé';
        }
        
        if ($booking->booking_status_id === 6) {
            return 'Le rendez-vous est déjà terminé';
        }
        
        if ($booking->booking_status_id === 7) {
            return 'Le rendez-vous a déjà échoué';
        }
        
        if ($booking->booking_status_id === 8) {
            return 'Le rendez-vous a été reporté';
        }
        
        return 'Conditions non remplies pour l\'annulation';
    } 

}
