<?php
/*
 * File name: BookingController.php
 * Last modified: 2024.04.10 at 12:26:06
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Http\Controllers;

use Illuminate\View\View;
use Laracasts\Flash\Flash;
use App\Events\BookingChangedEvent;
use App\DataTables\BookingDataTable;
use Illuminate\Http\RedirectResponse;
use App\Repositories\AddressRepository;
use App\Repositories\BookingRepository;
use App\Repositories\PaymentRepository;
use App\Events\BookingStatusChangedEvent;
use App\Http\Requests\UpdateBookingRequest;
use App\Repositories\CustomFieldRepository;
use App\Repositories\BookingStatusRepository;
use App\Repositories\PaymentStatusRepository;
use App\Criteria\Bookings\BookingsOfUserCriteria;
use App\Criteria\Addresses\AddressesOfUserCriteria;
use Prettus\Validator\Exceptions\ValidatorException;
use Prettus\Repository\Exceptions\RepositoryException;

class BookingController extends Controller
{
    /** @var  BookingRepository */
    private BookingRepository $bookingRepository;

    /**
     * @var CustomFieldRepository
     */
    private CustomFieldRepository $customFieldRepository;

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
     * @var PaymentStatusRepository
     */
    private PaymentStatusRepository $paymentStatusRepository;

    public function __construct(BookingRepository $bookingRepo, CustomFieldRepository $customFieldRepo
        , BookingStatusRepository                 $bookingStatusRepo, PaymentRepository $paymentRepo, AddressRepository $addressRepository, PaymentStatusRepository $paymentStatusRepository)
    {
        parent::__construct();
        $this->bookingRepository = $bookingRepo;
        $this->customFieldRepository = $customFieldRepo;
        $this->bookingStatusRepository = $bookingStatusRepo;
        $this->paymentRepository = $paymentRepo;
        $this->addressRepository = $addressRepository;
        $this->paymentStatusRepository = $paymentStatusRepository;
    }

    /**
     * Display a listing of the Booking.
     *
     * @param BookingDataTable $bookingDataTable
     * @return mixed
     */
    public function index(BookingDataTable $bookingDataTable): mixed
    {
        return $bookingDataTable->render('bookings.index');
    }

    /**
     * Display the specified Booking.
     *
     * @param int $id
     *
     * @return View|RedirectResponse
     * @throws RepositoryException
     */
    public function show(int $id): RedirectResponse|View
    {
        $this->bookingRepository->pushCriteria(new BookingsOfUserCriteria(auth()->id()));
        $booking = $this->bookingRepository->findWithoutFail($id);
        if (empty($booking)) {
            Flash::error(__('lang.not_found', ['operator' => __('lang.booking')]));
            return redirect(route('bookings.index'));
        }
        $bookingStatuses = $this->bookingStatusRepository->orderBy('order')->all();
        return view('bookings.show')->with('booking', $booking)->with('bookingStatuses', $bookingStatuses);
    }

    /**
     * Show the form for editing the specified Booking.
     *
     * @param int $id
     *
     * @return View|RedirectResponse
     * @throws RepositoryException
     */
    public function edit(int $id): RedirectResponse|View
    {
        $this->bookingRepository->pushCriteria(new BookingsOfUserCriteria(auth()->id()));
        $booking = $this->bookingRepository->findWithoutFail($id);
        if (empty($booking)) {
            Flash::error(__('lang.not_found', ['operator' => __('lang.booking')]));
            return redirect(route('bookings.index'));
        }
        $booking->fillable[] = ['address_id', 'payment_status_id'];
        $booking->address_id = $booking->address->id;
        $bookingStatus = $this->bookingStatusRepository->orderBy('order')->pluck('status', 'id');
        if (!empty($booking->payment_id)) {
            $booking->payment_status_id = $booking->payment->payment_status_id;
            $paymentStatuses = $this->paymentStatusRepository->pluck('status', 'id');
        } else {
            $paymentStatuses = null;
        }
        $addresses = $this->addressRepository->getByCriteria(new AddressesOfUserCriteria($booking->user_id))->pluck('address', 'id');

        $customFieldsValues = $booking->customFieldsValues()->with('customField')->get();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->bookingRepository->model());
        $hasCustomField = in_array($this->bookingRepository->model(), setting('custom_field_models', []));
        if ($hasCustomField) {
            $html = generateCustomField($customFields, $customFieldsValues);
        }
        return view('bookings.edit')->with('booking', $booking)->with("customFields", $html ?? false)->with("bookingStatus", $bookingStatus)->with("addresses", $addresses)->with("paymentStatuses", $paymentStatuses);
    }

    /**
     * Update the specified Booking in storage.
     *
     * @param int $id
     * @param UpdateBookingRequest $request
     *
     * @return RedirectResponse
     */
    public function update(int $id, UpdateBookingRequest $request): RedirectResponse
    {
        $oldBooking = $this->bookingRepository->findWithoutFail($id);
        if (empty($oldBooking)) {
            Flash::error(__('lang.not_found', ['operator' => __('lang.booking')]));
            return redirect(route('bookings.index'));
        }
        $input = $request->all();
        $address = $this->addressRepository->findWithoutFail($input['address_id']);
        $input['address'] = $address;
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->bookingRepository->model());
        try {
            if (isset($input['cancel']) && $input['cancel'] == '1') {
                $input['payment_status_id'] = 3; // failed
                $input['booking_status_id'] = 7; // failed
            }
            
            $booking = $this->bookingRepository->update($input, $id);
            if (isset($input['payment_status_id'])) {
                $this->paymentRepository->update(
                    ['payment_status_id' => $input['payment_status_id']],
                    $booking->payment_id
                );
                event(new BookingChangedEvent($booking));
            }
            if (isset($input['booking_status_id']) && $input['booking_status_id'] != $oldBooking->booking_status_id) {
                event(new BookingStatusChangedEvent($booking));
            }

            foreach (getCustomFieldsValues($customFields, $request) as $value) {
                $booking->customFieldsValues()
                    ->updateOrCreate(['custom_field_id' => $value['custom_field_id']], $value);
            }
        } catch (ValidatorException $e) {
            Flash::error($e->getMessage());
        }
        Flash::success(__('lang.updated_successfully', ['operator' => __('lang.booking')]));
        return redirect(route('bookings.index'));
    }

    /**
     * Remove the specified Booking from storage.
     *
     * @param int $id
     *
     * @return RedirectResponse
     * @throws RepositoryException
     */
    public function destroy(int $id): RedirectResponse
    {
        if (!config('installer.demo_app')) {
            $this->bookingRepository->pushCriteria(new BookingsOfUserCriteria(auth()->id()));
            $booking = $this->bookingRepository->findWithoutFail($id);

            if (empty($booking)) {
                Flash::error(__('lang.not_found', ['operator' => __('lang.booking')]));

                return redirect(route('bookings.index'));
            }

            $this->bookingRepository->delete($id);

            Flash::success(__('lang.deleted_successfully', ['operator' => __('lang.booking')]));

        } else {
            Flash::warning('This is only demo app you can\'t change this section ');
        }
        return redirect(route('bookings.index'));
    }

}
