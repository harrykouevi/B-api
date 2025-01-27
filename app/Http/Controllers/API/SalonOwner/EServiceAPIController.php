<?php
/*
 * File name: EServiceAPIController.php
 * Last modified: 2024.04.18 at 17:22:51
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Http\Controllers\API\SalonOwner;


use App\Criteria\EServices\EServicesOfSalonCriteria;
use App\Criteria\EServices\NearCriteria;
use App\Http\Controllers\Controller;
use App\Repositories\EServiceRepository;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Prettus\Repository\Criteria\RequestCriteria;

/**
 * Class EServiceController
 * @package App\Http\Controllers\API
 */
class EServiceAPIController extends Controller
{
    /** @var  eServiceRepository */
    private EServiceRepository $eServiceRepository;

    public function __construct(EServiceRepository $eServiceRepo)
    {
        parent::__construct();
        $this->eServiceRepository = $eServiceRepo;
    }

    /**
     * Display a listing of the EService.
     * GET|HEAD /eServices
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $this->eServiceRepository->pushCriteria(new RequestCriteria($request));
            $this->eServiceRepository->pushCriteria(new EServicesOfSalonCriteria(auth()->id()));
            $this->eServiceRepository->pushCriteria(new NearCriteria($request));
            $eServices = $this->eServiceRepository->all();

            $this->availableSalon($request, $eServices);
            $this->limitOffset($request, $eServices);
            $this->filterCollection($request, $eServices);
            $eServices = array_values($eServices->toArray());
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendResponse($eServices, 'E Services retrieved successfully');
    }

    /**
     * @param Request $request
     * @param Collection $eServices
     */
    private function availableSalon(Request $request, Collection &$eServices): void
    {
        if ($request->has('available_salon')) {
            $eServices = $eServices->filter(function ($element) {
                return !$element->salon->closed;
            });
        }
    }

}
