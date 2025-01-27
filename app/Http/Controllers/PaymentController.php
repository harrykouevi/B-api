<?php
/*
 * File name: PaymentController.php
 * Last modified: 2024.04.10 at 14:47:28
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Http\Controllers;

use App\DataTables\PaymentDataTable;
use Illuminate\Support\Facades\Response;

class PaymentController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Display a listing of the Payment.
     *
     * @param PaymentDataTable $paymentDataTable
     * @return Response
     */
    public function index(PaymentDataTable $paymentDataTable): mixed
    {
        return $paymentDataTable->render('payments.index');
    }
}
