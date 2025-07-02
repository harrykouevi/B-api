<?php
/*
 * File name: PaymentController.php
 * Last modified: 2024.04.10 at 14:47:28
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Http\Controllers;

use App\Criteria\Payments\PaymentsOfUserCriteria;
use App\DataTables\PaymentDataTable;
use App\Models\PaymentMethod;
use App\Models\PaymentStatus;
use App\Repositories\PaymentRepository;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Flash;



class PaymentController extends Controller
{
    /** @var  PaymentRepository */
    private PaymentRepository $paymentRepository;

    public function __construct(PaymentRepository $paymentRepo)
    {
        parent::__construct();
    $this->paymentRepository = $paymentRepo;
    }

    /**
     * Display the specified payment.
     *
     * @param int $id
     *
     * @return RedirectResponse|View
     * @throws RepositoryException
     */
    public function show(int $id): RedirectResponse|View
    {
        $this->paymentRepository->pushCriteria(new PaymentsOfUserCriteria(auth()->id()));
        $payment = $this->paymentRepository->findWithoutFail($id);

        if (empty($payment)) {
            Flash::error(__('lang.not_found', ['operator' => __('lang.salon_review')]));
            return redirect(route('payments.index'));
        }
        return view('payments.show')->with('payment', $payment);
     }

    /**
     * Display a listing of the Payment.
     *
     * @param PaymentDataTable $paymentDataTable
     * @return Response
     */
    public function index(PaymentDataTable $paymentDataTable): mixed
    {
         $paymentStatuses = PaymentStatus::all();
        $paymentMethods = PaymentMethod::all();
        return $paymentDataTable->render('payments.index', compact('paymentStatuses', 'paymentMethods'));
    }
}
