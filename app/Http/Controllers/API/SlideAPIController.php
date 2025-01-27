<?php
/*
 * File name: SlideAPIController.php
 * Last modified: 2024.04.10 at 14:47:28
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Http\Controllers\API;


use App\Criteria\Slides\EnabledCriteria;
use App\Criteria\Slides\OrderCriteria;
use App\Http\Controllers\Controller;
use App\Models\Slide;
use App\Repositories\SlideRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InfyOm\Generator\Criteria\LimitOffsetCriteria;
use Prettus\Repository\Criteria\RequestCriteria;
use Prettus\Repository\Exceptions\RepositoryException;

/**
 * Class SlideController
 * @package App\Http\Controllers\API
 */
class SlideAPIController extends Controller
{
    /** @var  SlideRepository */
    private SlideRepository $slideRepository;

    public function __construct(SlideRepository $slideRepo)
    {
        parent::__construct();
        $this->slideRepository = $slideRepo;
    }

    /**
     * Display a listing of the Slide.
     * GET|HEAD /slides
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $this->slideRepository->pushCriteria(new RequestCriteria($request));
            $this->slideRepository->pushCriteria(new LimitOffsetCriteria($request));
            $this->slideRepository->pushCriteria(new OrderCriteria());
            $this->slideRepository->pushCriteria(new EnabledCriteria());
        } catch (RepositoryException $e) {
            return $this->sendError($e->getMessage());
        }
        $slides = $this->slideRepository->all();

        return $this->sendResponse($slides->toArray(), 'Slides retrieved successfully');
    }

    /**
     * Display the specified Slide.
     * GET|HEAD /slides/{id}
     *
     * @param int $id
     *
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        /** @var Slide $slide */
        if (!empty($this->slideRepository)) {
            $slide = $this->slideRepository->findWithoutFail($id);
        }

        if (empty($slide)) {
            return $this->sendError('Slide not found');
        }

        return $this->sendResponse($slide->toArray(), 'Slide retrieved successfully');
    }
}
