<?php
/*
 * File name: PaymentMethodAPIController.php
 * Last modified: 2024.04.10 at 14:47:27
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Http\Controllers\API;


use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Repositories\PaymentMethodRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InfyOm\Generator\Criteria\LimitOffsetCriteria;
use Prettus\Repository\Criteria\RequestCriteria;
use Prettus\Repository\Exceptions\RepositoryException;
use Illuminate\Support\Collection;


/**
 * Class PaymentMethodController
 * @package App\Http\Controllers\API
 */
class PaymentMethodAPIController extends Controller
{
    /** @var  PaymentMethodRepository */
    private PaymentMethodRepository $paymentMethodRepository;

    public function __construct(PaymentMethodRepository $paymentMethodRepo)
    {
        parent::__construct();
        $this->paymentMethodRepository = $paymentMethodRepo;
    }

     /**
     *@param Request $request
     * @param Collection $collection
     * @return Collection $collection
     */
    protected function _filter(Request $request, Collection $collection):  Collection
    {
        if($request->has('is_gateway_method')){
            $bool = $request->input('is_gateway_method');
            $collection = $collection->filter( PaymentMethod::scopedFilter($bool) );
        }
        return $collection ;
    }


    /**
     * Display a listing of the PaymentMethod.
     * GET|HEAD /paymentMethods
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        
        try {
            $this->paymentMethodRepository->pushCriteria(new RequestCriteria($request));
            $this->paymentMethodRepository->pushCriteria(new LimitOffsetCriteria($request));
        } catch (RepositoryException $e) {
            return $this->sendError($e->getMessage());
        }
        $paymentMethods = $this->paymentMethodRepository->all();
        $paymentMethods = $this->_filter($request,$paymentMethods);

        return $this->sendResponse($paymentMethods->toArray(), 'Payment Methods retrieved successfully');
    }

    /**
     * Display the specified PaymentMethod.
     * GET|HEAD /paymentMethods/{id}
     *
     * @param int $id
     *
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        /** @var PaymentMethod $paymentMethod */
        if (!empty($this->paymentMethodRepository)) {
            $paymentMethod = $this->paymentMethodRepository->findWithoutFail($id);
        }

        if (empty($paymentMethod)) {
            return $this->sendError('Payment Method not found');
        }

        return $this->sendResponse($paymentMethod->toArray(), 'Payment Method retrieved successfully');
    }
}
