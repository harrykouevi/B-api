<?php
/*
 * File name: SalonAPIController.php
 * Last modified: 2024.04.10 at 14:21:46
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Http\Controllers\API\SalonOwner;


use App\Criteria\Salons\SalonsOfUserCriteria;
use App\Http\Controllers\Controller;
use App\Repositories\SalonRepository;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InfyOm\Generator\Criteria\LimitOffsetCriteria;
use Prettus\Repository\Criteria\RequestCriteria;

/**
 * Class SalonController
 * @package App\Http\Controllers\API
 */
class SalonAPIController extends Controller
{
    /** @var  SalonRepository */
    private SalonRepository $salonRepository;

    public function __construct(SalonRepository $salonRepo)
    {
        $this->salonRepository = $salonRepo;
        parent::__construct();
    }

    /**
     * Display a listing of the Salon.
     * GET|HEAD /salons
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $this->salonRepository->pushCriteria(new RequestCriteria($request));
            $this->salonRepository->pushCriteria(new SalonsOfUserCriteria(auth()->id()));
            $this->salonRepository->pushCriteria(new LimitOffsetCriteria($request));
            $salons = $this->salonRepository->all();
            $this->filterCollection($request, $salons);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse($salons->toArray(), 'Salons retrieved successfully');
    }

    /**
     * Display the specified Salon.
     * GET|HEAD /salons/{id}
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function show(int $id, Request $request): JsonResponse
    {
        try {
            $this->salonRepository->pushCriteria(new RequestCriteria($request));
            $this->salonRepository->pushCriteria(new LimitOffsetCriteria($request));
            $salon = $this->salonRepository->findWithoutFail($id);
            if (empty($salon)) {
                return $this->sendError('Salon not found');
            }
            $this->filterModel($request, $salon);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse($salon->toArray(), 'Salon retrieved successfully');
    }
}
