<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOptionTemplateRequest;
use App\Http\Requests\UpdateOptionTemplateRequest;
use App\Repositories\OptionTemplateRepository;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InfyOm\Generator\Criteria\LimitOffsetCriteria;
use Prettus\Repository\Criteria\RequestCriteria;
use Prettus\Repository\Exceptions\RepositoryException;

/**
 * Class OptionTemplateAPIController
 * @package App\Http\Controllers\API
 */
class OptionTemplateAPIController extends Controller
{
    /** @var OptionTemplateRepository */
    private OptionTemplateRepository $optionTemplateRepository;

    public function __construct(OptionTemplateRepository $optionTemplateRepo)
    {
        parent::__construct();
        $this->optionTemplateRepository = $optionTemplateRepo;
    }

    /**
     * Display a listing of the OptionTemplate.
     * GET|HEAD /option-templates
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $this->optionTemplateRepository->pushCriteria(new RequestCriteria($request));
            $this->optionTemplateRepository->pushCriteria(new LimitOffsetCriteria($request));
        } catch (RepositoryException $e) {
            return $this->sendError($e->getMessage());
        }
        
        $optionTemplates = $this->optionTemplateRepository->all();
        $this->filterCollection($request, $optionTemplates);

        return $this->sendResponse($optionTemplates->toArray(), 'Option Templates retrieved successfully');
    }

    /**
     * Display the specified OptionTemplate.
     * GET|HEAD /option-templates/{id}
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function show(int $id, Request $request): JsonResponse
    {
        try {
            $this->optionTemplateRepository->pushCriteria(new RequestCriteria($request));
        } catch (RepositoryException $e) {
            return $this->sendError($e->getMessage());
        }
        
        $optionTemplate = $this->optionTemplateRepository->findWithoutFail($id);
        
        if (empty($optionTemplate)) {
            return $this->sendError('Option Template not found');
        }
        
        $this->filterModel($request, $optionTemplate);
        
        return $this->sendResponse($optionTemplate->toArray(), 'Option Template retrieved successfully');
    }

    /**
     * Store a newly created OptionTemplate in storage.
     * POST /option-templates
     *
     * @param CreateOptionTemplateRequest $request
     * @return JsonResponse
     */
    public function store(CreateOptionTemplateRequest $request): JsonResponse
    {
        $input = $request->all();
        
        try {
            $optionTemplate = $this->optionTemplateRepository->create($input);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
        
        return $this->sendResponse($optionTemplate->toArray(), 'Option Template saved successfully');
    }

    /**
     * Update the specified OptionTemplate in storage.
     * PUT/PATCH /option-templates/{id}
     *
     * @param int $id
     * @param UpdateOptionTemplateRequest $request
     * @return JsonResponse
     */
    public function update(int $id, UpdateOptionTemplateRequest $request): JsonResponse
    {
        $optionTemplate = $this->optionTemplateRepository->findWithoutFail($id);

        if (empty($optionTemplate)) {
            return $this->sendError('Option Template not found');
        }
        
        $input = $request->all();
        
        try {
            $optionTemplate = $this->optionTemplateRepository->update($input, $id);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
        
        return $this->sendResponse($optionTemplate->toArray(), 'Option Template updated successfully');
    }

    /**
     * Remove the specified OptionTemplate from storage.
     * DELETE /option-templates/{id}
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $optionTemplate = $this->optionTemplateRepository->findWithoutFail($id);
        
        if (empty($optionTemplate)) {
            return $this->sendError('Option Template not found');
        }
        
        try {
            $this->optionTemplateRepository->delete($id);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
        
        return $this->sendResponse($optionTemplate->toArray(), 'Option Template deleted successfully');
    }

    /**
     * Get option templates by service template ID.
     * GET /option-templates/by-service/{serviceTemplateId}
     *
     * @param int $serviceTemplateId
     * @param Request $request
     * @return JsonResponse
     */
    public function byServiceTemplate(int $serviceTemplateId, Request $request): JsonResponse
    {
        try {
            $this->optionTemplateRepository->pushCriteria(new RequestCriteria($request));
            $this->optionTemplateRepository->pushCriteria(new LimitOffsetCriteria($request));
            
            $optionTemplates = $this->optionTemplateRepository->findByField('service_template_id', $serviceTemplateId);
            
            $this->filterCollection($request, $optionTemplates);
            
            return $this->sendResponse($optionTemplates->toArray(), 'Option Templates retrieved successfully');
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
}
