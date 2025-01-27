<?php
/*
 * File name: FaqAPIController.php
 * Last modified: 2024.04.10 at 14:47:27
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Http\Controllers\API;


use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Repositories\FaqRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InfyOm\Generator\Criteria\LimitOffsetCriteria;
use Prettus\Repository\Criteria\RequestCriteria;
use Prettus\Repository\Exceptions\RepositoryException;

/**
 * Class FaqController
 * @package App\Http\Controllers\API
 */
class FaqAPIController extends Controller
{
    /** @var  FaqRepository */
    private FaqRepository $faqRepository;

    public function __construct(FaqRepository $faqRepo)
    {
        parent::__construct();
        $this->faqRepository = $faqRepo;
    }

    /**
     * Display a listing of the Faq.
     * GET|HEAD /faqs
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $this->faqRepository->pushCriteria(new RequestCriteria($request));
            $this->faqRepository->pushCriteria(new LimitOffsetCriteria($request));
        } catch (RepositoryException $e) {
            return $this->sendError($e->getMessage());
        }
        $faqs = $this->faqRepository->all();

        return $this->sendResponse($faqs->toArray(), 'Faqs retrieved successfully');
    }

    /**
     * Display the specified Faq.
     * GET|HEAD /faqs/{id}
     *
     * @param int $id
     *
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        /** @var Faq $faq */
        if (!empty($this->faqRepository)) {
            $faq = $this->faqRepository->findWithoutFail($id);
        }

        if (empty($faq)) {
            return $this->sendError('Faq not found');
        }

        return $this->sendResponse($faq->toArray(), 'Faq retrieved successfully');
    }
}
