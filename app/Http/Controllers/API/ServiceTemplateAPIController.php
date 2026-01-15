<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateServiceTemplateRequest;
use App\Http\Requests\UpdateServiceTemplateRequest;
use App\Repositories\ServiceTemplateRepository;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InfyOm\Generator\Criteria\LimitOffsetCriteria;
use Prettus\Repository\Criteria\RequestCriteria;
use Prettus\Repository\Exceptions\RepositoryException;

/**
 * Class ServiceTemplateAPIController
 * @package App\Http\Controllers\API
 */
class ServiceTemplateAPIController extends Controller
{
    /** @var ServiceTemplateRepository */
    private ServiceTemplateRepository $serviceTemplateRepository;

    public function __construct(ServiceTemplateRepository $serviceTemplateRepo)
    {
        parent::__construct();
        $this->serviceTemplateRepository = $serviceTemplateRepo;
    }

    /**
     * Display a listing of the ServiceTemplate.
     * GET|HEAD /service-templates
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $this->serviceTemplateRepository->pushCriteria(new RequestCriteria($request));
            $this->serviceTemplateRepository->pushCriteria(new LimitOffsetCriteria($request));
        } catch (RepositoryException $e) {
            return $this->sendError($e->getMessage());
        }
        
        $serviceTemplates = $this->serviceTemplateRepository->all();
        $this->filterCollection($request, $serviceTemplates);

        return $this->sendResponse($serviceTemplates->toArray(), 'Service Templates retrieved successfully');
    }

    /**
     * Display the specified ServiceTemplate.
     * GET|HEAD /service-templates/{id}
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function show(int $id, Request $request): JsonResponse
    {
        try {
            $this->serviceTemplateRepository->pushCriteria(new RequestCriteria($request));
        } catch (RepositoryException $e) {
            return $this->sendError($e->getMessage());
        }
        
        $serviceTemplate = $this->serviceTemplateRepository->findWithoutFail($id);
        
        if (empty($serviceTemplate)) {
            return $this->sendError('Service Template not found');
        }
        
        $this->filterModel($request, $serviceTemplate);
        
        return $this->sendResponse($serviceTemplate->toArray(), 'Service Template retrieved successfully');
    }

    /**
     * Store a newly created ServiceTemplate in storage.
     * POST /service-templates
     *
     * @param CreateServiceTemplateRequest $request
     * @return JsonResponse
     */
    public function store(CreateServiceTemplateRequest $request): JsonResponse
    {
        $input = $request->all();
        
        try {
            $serviceTemplate = $this->serviceTemplateRepository->create($input);
            //$serviceTemplate->load(['category']);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
        
        return $this->sendResponse($serviceTemplate->toArray(), 'Service Template saved successfully');
    }

    /**
     * Update the specified ServiceTemplate in storage.
     * PUT/PATCH /service-templates/{id}
     *
     * @param int $id
     * @param UpdateServiceTemplateRequest $request
     * @return JsonResponse
     */
    public function update(int $id, UpdateServiceTemplateRequest $request): JsonResponse
    {
        $serviceTemplate = $this->serviceTemplateRepository->findWithoutFail($id);

        if (empty($serviceTemplate)) {
            return $this->sendError('Service Template not found');
        }
        
        $input = $request->all();
        
        try {
            $serviceTemplate = $this->serviceTemplateRepository->update($input, $id);
            //$serviceTemplate->load(['category']);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
        
        return $this->sendResponse($serviceTemplate->toArray(), 'Service Template updated successfully');
    }

    /**
     * Remove the specified ServiceTemplate from storage.
     * DELETE /service-templates/{id}
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $serviceTemplate = $this->serviceTemplateRepository->findWithoutFail($id);
        
        if (empty($serviceTemplate)) {
            return $this->sendError('Service Template not found');
        }
        
        try {
            $this->serviceTemplateRepository->delete($id);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
        
        return $this->sendResponse($serviceTemplate->toArray(), 'Service Template deleted successfully');
    }
}