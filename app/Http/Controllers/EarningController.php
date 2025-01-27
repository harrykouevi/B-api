<?php
/*
 * File name: EarningController.php
 * Last modified: 2024.04.10 at 14:47:27
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Http\Controllers;

use App\Criteria\Bookings\BookingsOfSalonCriteria;
use App\Criteria\Bookings\PaidBookingsCriteria;
use App\DataTables\EarningDataTable;
use App\Http\Requests\CreateEarningRequest;
use App\Repositories\BookingRepository;
use App\Repositories\CustomFieldRepository;
use App\Repositories\EarningRepository;
use App\Repositories\SalonRepository;
use Flash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;
use Prettus\Repository\Exceptions\RepositoryException;
use Prettus\Validator\Exceptions\ValidatorException;

class EarningController extends Controller
{
    /** @var  EarningRepository */
    private EarningRepository $earningRepository;

    /**
     * @var CustomFieldRepository
     */
    private CustomFieldRepository $customFieldRepository;

    /**
     * @var SalonRepository
     */
    private SalonRepository $salonRepository;

    /**
     * @var BookingRepository
     */
    private BookingRepository $bookingRepository;

    public function __construct(EarningRepository $earningRepo, CustomFieldRepository $customFieldRepo, SalonRepository $salonRepo, BookingRepository $bookingRepository)
    {
        parent::__construct();
        $this->earningRepository = $earningRepo;
        $this->customFieldRepository = $customFieldRepo;
        $this->salonRepository = $salonRepo;
        $this->bookingRepository = $bookingRepository;
    }

    /**
     * Display a listing of the Earning.
     *
     * @param EarningDataTable $earningDataTable
     * @return Response
     */
    public function index(EarningDataTable $earningDataTable): mixed
    {
        return $earningDataTable->render('earnings.index');
    }

    /**
     * Store a newly created Earning in storage.
     *
     * @param CreateEarningRequest $request
     *
     * @return RedirectResponse
     */
    public function store(CreateEarningRequest $request): RedirectResponse
    {
        $input = $request->all();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->earningRepository->model());
        try {
            $earning = $this->earningRepository->create($input);
            $earning->customFieldsValues()->createMany(getCustomFieldsValues($customFields, $request));

        } catch (ValidatorException $e) {
            Flash::error($e->getMessage());
        }

        Flash::success(__('lang.saved_successfully', ['operator' => __('lang.earning')]));

        return redirect(route('earnings.index'));
    }

    /**
     * Show the form for creating a new Earning.
     *
     * @return RedirectResponse|View
     */
    public function create(): RedirectResponse|View
    {
        $salons = $this->salonRepository->all();
        foreach ($salons as $salon) {
            try {
                $this->bookingRepository->pushCriteria(new BookingsOfSalonCriteria($salon->id));
                $this->bookingRepository->pushCriteria(new PaidBookingsCriteria());
                $bookings = $this->bookingRepository->all();
                $bookingsCount = $bookings->count();

                $bookingsTotals = $bookings->map(function ($booking) {
                    return $booking->getTotal();
                })->toArray();

                $bookingsTaxes = $bookings->map(function ($booking) {
                    return $booking->getTaxesValue();
                })->toArray();

                $total = array_reduce($bookingsTotals, function ($total1, $total2) {
                    return $total1 + $total2;
                }, 0);

                $tax = array_reduce($bookingsTaxes, function ($tax1, $tax2) {
                    return $tax1 + $tax2;
                }, 0);
                $this->earningRepository->updateOrCreate(['salon_id' => $salon->id], [
                        'total_bookings' => $bookingsCount,
                        'total_earning' => $total - $tax,
                        'taxes' => $tax,
                        'admin_earning' => ($total - $tax) * (100 - $salon->salonLevel->commission) / 100,
                        'salon_earning' => ($total - $tax) * $salon->salonLevel->commission / 100,
                    ]
                );
            } catch (ValidatorException|RepositoryException) {
            } finally {
                $this->bookingRepository->resetCriteria();
            }
        }
        return redirect(route('earnings.index'));
    }

    /**
     * Remove the specified Earning from storage.
     *
     * @param int $id
     *
     * @return RedirectResponse
     */
    public function destroy(int $id): RedirectResponse
    {
        $earning = $this->earningRepository->findWithoutFail($id);

        if (empty($earning)) {
            Flash::error(__('lang.not_found', ['operator' => __('lang.earning')]));

            return redirect(route('earnings.index'));
        }

        $this->earningRepository->delete($id);

        Flash::success(__('lang.deleted_successfully', ['operator' => __('lang.earning')]));
        return redirect(route('earnings.index'));
    }
}
