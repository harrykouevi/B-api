<?php
/*
 * File name: EarningAPIController.php
 * Last modified: 2024.04.10 at 14:47:28
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Http\Controllers\API;


use App\Http\Controllers\Controller;
use App\Models\Earning;
use App\Repositories\EarningRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InfyOm\Generator\Criteria\LimitOffsetCriteria;
use Prettus\Repository\Criteria\RequestCriteria;
use Prettus\Repository\Exceptions\RepositoryException;

/**
 * Class EarningController
 * @package App\Http\Controllers\API
 */
class EarningAPIController extends Controller
{
    /** @var  EarningRepository */
    private EarningRepository $earningRepository;

    public function __construct(EarningRepository $earningRepo)
    {
        parent::__construct();
        $this->earningRepository = $earningRepo;
    }

    /**
     * Display a listing of the Earning.
     * GET|HEAD /earnings
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $this->earningRepository->pushCriteria(new RequestCriteria($request));
            $this->earningRepository->pushCriteria(new LimitOffsetCriteria($request));
        } catch (RepositoryException $e) {
            return $this->sendError($e->getMessage());
        }
        $earnings = $this->earningRepository->all();

        return $this->sendResponse($earnings->toArray(), 'Earnings retrieved successfully');
    }

    /**
     * Display the specified Earning.
     * GET|HEAD /earnings/{id}
     *
     * @param int $id
     *
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        /** @var Earning $earning */
        if (!empty($this->earningRepository)) {
            $earning = $this->earningRepository->findWithoutFail($id);
        }

        if (empty($earning)) {
            return $this->sendError('Earning not found');
        }

        return $this->sendResponse($earning->toArray(), 'Earning retrieved successfully');
    }
}
