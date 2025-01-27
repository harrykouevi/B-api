<?php
/*
 * File name: FaqCategoryAPIController.php
 * Last modified: 2024.04.10 at 14:47:27
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Http\Controllers\API;


use App\Http\Controllers\Controller;
use App\Models\FaqCategory;
use App\Repositories\FaqCategoryRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InfyOm\Generator\Criteria\LimitOffsetCriteria;
use Prettus\Repository\Criteria\RequestCriteria;
use Prettus\Repository\Exceptions\RepositoryException;

/**
 * Class FaqCategoryController
 * @package App\Http\Controllers\API
 */
class FaqCategoryAPIController extends Controller
{
    /** @var  FaqCategoryRepository */
    private FaqCategoryRepository $faqCategoryRepository;

    public function __construct(FaqCategoryRepository $faqCategoryRepo)
    {
        parent::__construct();
        $this->faqCategoryRepository = $faqCategoryRepo;
    }

    /**
     * Display a listing of the FaqCategory.
     * GET|HEAD /faqCategories
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $this->faqCategoryRepository->pushCriteria(new RequestCriteria($request));
            $this->faqCategoryRepository->pushCriteria(new LimitOffsetCriteria($request));
        } catch (RepositoryException $e) {
            return $this->sendError($e->getMessage());
        }
        $faqCategories = $this->faqCategoryRepository->all();
        $this->filterCollection($request, $faqCategories);

        return $this->sendResponse($faqCategories->toArray(), 'Faq Categories retrieved successfully');
    }

    /**
     * Display the specified FaqCategory.
     * GET|HEAD /faqCategories/{id}
     *
     * @param int $id
     *
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        /** @var FaqCategory $faqCategory */
        if (!empty($this->faqCategoryRepository)) {
            $faqCategory = $this->faqCategoryRepository->findWithoutFail($id);
        }

        if (empty($faqCategory)) {
            return $this->sendError('Faq Category not found');
        }

        return $this->sendResponse($faqCategory->toArray(), 'Faq Category retrieved successfully');
    }
}
