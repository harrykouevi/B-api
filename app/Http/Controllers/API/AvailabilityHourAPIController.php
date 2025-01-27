<?php
/*
 * File name: AvailabilityHourAPIController.php
 * Last modified: 2024.04.10 at 11:47:06
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Http\Controllers\API;


use App\Criteria\AvailabilityHours\AvailabilityHoursOfUserCriteria;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateAvailabilityHourRequest;
use App\Http\Requests\UpdateAvailabilityHourRequest;
use App\Repositories\AvailabilityHourRepository;
use App\Repositories\SalonRepository;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InfyOm\Generator\Criteria\LimitOffsetCriteria;
use Prettus\Repository\Criteria\RequestCriteria;
use Prettus\Repository\Exceptions\RepositoryException;

/**
 * Class AvailabilityHourController
 * @package App\Http\Controllers\API
 */
class AvailabilityHourAPIController extends Controller
{
    /** @var  AvailabilityHourRepository */
    private AvailabilityHourRepository $availabilityHourRepository;

    /** @var  SalonRepository */
    private SalonRepository $salonRepository;


    public function __construct(AvailabilityHourRepository $availabilityHourRepo, SalonRepository $salonRepo)
    {
        parent::__construct();
        $this->availabilityHourRepository = $availabilityHourRepo;
        $this->salonRepository = $salonRepo;
    }


    /**
     * Display a listing of the AvailabilityHour.
     * GET|HEAD /availabilityHours
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $this->availabilityHourRepository->pushCriteria(new RequestCriteria($request));
            $this->availabilityHourRepository->pushCriteria(new LimitOffsetCriteria($request));
            $availabilityHours = $this->availabilityHourRepository->all();
            $this->filterCollection($request, $availabilityHours);
        } catch (RepositoryException $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse($availabilityHours->toArray(), 'Availability Hours retrieved successfully');
    }

    /**
     * Display the specified AvailabilityHour.
     * GET|HEAD /availabilityHours/{id}
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
        } catch (RepositoryException $e) {
            return $this->sendError($e->getMessage());
        }
        $employeeId = $request->get('employee_id', 0);
        $salon = $this->salonRepository->findWithoutFail($id);
        if (empty($salon)) {
            return $this->sendError('Salon not found');
        }
        $calendar = [];
        $date = $request->input('date');
        if (!empty($date)) {
            $date = Carbon::createFromFormat('Y-m-d', $date);
            $calendar = $salon->weekCalendarRange($date, $employeeId);
        }

        return $this->sendResponse($calendar, 'Availability Hours retrieved successfully');

    }

    /**
     * Store a newly created AvailabilityHour in storage.
     *
     * @param CreateAvailabilityHourRequest $request
     *
     * @return JsonResponse
     */
    public function store(CreateAvailabilityHourRequest $request): JsonResponse
    {
        $input = $request->all();
        try {
            $availabilityHour = $this->availabilityHourRepository->create($input);

        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendResponse($availabilityHour, __('lang.saved_successfully', ['operator' => __('lang.availability_hour')]));
    }

    /**
     * Update the specified AvailabilityHour in storage.
     *
     * @param int $id
     * @param UpdateAvailabilityHourRequest $request
     *
     * @return JsonResponse
     * @throws RepositoryException
     */
    public function update(int $id, UpdateAvailabilityHourRequest $request): JsonResponse
    {
        $this->availabilityHourRepository->pushCriteria(new AvailabilityHoursOfUserCriteria(auth()->id()));
        $availabilityHour = $this->availabilityHourRepository->findWithoutFail($id);

        if (empty($availabilityHour)) {
            return $this->sendError(__('lang.not_found', ['operator' => __('lang.availability_hour')]));
        }
        $input = $request->all();
        try {
            $availabilityHour = $this->availabilityHourRepository->update($input, $id);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendResponse($availabilityHour, __('lang.updated_successfully', ['operator' => __('lang.availability_hour')]));
    }

    /**
     * Remove the specified AvailabilityHour from storage.
     *
     * @param int $id
     *
     * @return JsonResponse
     * @throws RepositoryException
     */
    public function destroy(int $id): JsonResponse
    {
        $this->availabilityHourRepository->pushCriteria(new AvailabilityHoursOfUserCriteria(auth()->id()));
        $availabilityHour = $this->availabilityHourRepository->findWithoutFail($id);

        if (empty($availabilityHour)) {
            return $this->sendError(__('lang.not_found', ['operator' => __('lang.availability_hour')]));
        }

        $this->availabilityHourRepository->delete($id);
        return $this->sendResponse($availabilityHour, __('lang.deleted_successfully', ['operator' => __('lang.availability_hour')]));
    }
}
