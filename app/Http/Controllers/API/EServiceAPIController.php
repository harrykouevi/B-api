<?php
/*
 * File name: EServiceAPIController.php
 * Last modified: 2024.04.18 at 17:22:51
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Http\Controllers\API;


use Illuminate\Validation\ValidationException;
use App\Criteria\EServices\EServicesOfUserCriteria;
use App\Criteria\EServices\NearCriteria;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateEServiceRequest;
use App\Http\Requests\CreateEServiceFromTemplateRequest;
use App\Http\Requests\UpdateEServiceRequest;
use App\Http\Requests\UpdateEServiceFromTemplateRequest;
use App\Models\EService;
use App\Repositories\EServiceRepository;
use App\Repositories\ServiceTemplateRepository;
use App\Repositories\UploadRepository;
use App\Repositories\UserRepository;
use App\Services\EServiceFromTemplateService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use InfyOm\Generator\Criteria\LimitOffsetCriteria;
use Nwidart\Modules\Facades\Module;
use Prettus\Repository\Criteria\RequestCriteria;
use Prettus\Repository\Exceptions\RepositoryException;


/**
 * Class EServiceController
 * @package App\Http\Controllers\API
 */
class EServiceAPIController extends Controller
{
    /** @var  eServiceRepository */
    private EServiceRepository $eServiceRepository;
    /** @var UserRepository */
    private UserRepository $userRepository;
    /**
     * @var UploadRepository
     */
    private UploadRepository $uploadRepository;
    /**
     * @var EServiceFromTemplateService
     */
    private EServiceFromTemplateService $eServiceFromTemplateService;

      /**
     * @var ServiceTemplateRepository
     */
    private ServiceTemplateRepository $serviceTemplateRepository;

    public function __construct(
        EServiceRepository $eServiceRepo,
        UserRepository $userRepository,
        UploadRepository $uploadRepository,
        EServiceFromTemplateService $eServiceFromTemplateService
    ) {
        parent::__construct();
        $this->eServiceRepository = $eServiceRepo;
        $this->userRepository = $userRepository;
        $this->uploadRepository = $uploadRepository;
        $this->eServiceFromTemplateService = $eServiceFromTemplateService;
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
            $this->eServiceRepository->pushCriteria(new EServicesOfUserCriteria(auth()->id()));
            $this->eServiceRepository->pushCriteria(new NearCriteria($request));
            $eServices = $this->eServiceRepository->all();

            $this->availableEServices($eServices);
            $this->availableSalon($request, $eServices);
            $this->hasValidSubscription($request, $eServices);
            $this->limitOffset($request, $eServices);
            $this->filterCollection($request, $eServices);
            $eServices = array_values($eServices->toArray());
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendResponse($eServices, 'E Services retrieved successfully');
    }

    /**
     * @param Collection $eServices
     */
    private function availableEServices(Collection &$eServices): void
    {
        $eServices = $eServices->where('available', true);
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

    /**
     * @param Request $request
     * @param Collection $eServices
     */
    private function hasValidSubscription(Request $request, Collection &$eServices): void
    {
        if (Module::isActivated('Subscription')) {
            $eServices = $eServices->filter(function ($element) {
                return $element->salon->hasValidSubscription && $element->salon->accepted;
            });
        } else {
            $eServices = $eServices->filter(function ($element) {
                return $element->salon->accepted;
            });
        }
    }

    /**
     * Display the specified EService.
     * GET|HEAD /eServices/{id}
     *
     * @param Request $request
     * @param int $id
     *
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $this->eServiceRepository->pushCriteria(new RequestCriteria($request));
            $this->eServiceRepository->pushCriteria(new LimitOffsetCriteria($request));
        } catch (RepositoryException $e) {
            return $this->sendError($e->getMessage());
        }
        $eService = $this->eServiceRepository->findWithoutFail($id);
        if (empty($eService)) {
            return $this->sendError('EService not found');
        }
        if ($request->has('api_token')) {
            $user = $this->userRepository->findByField('api_token', $request->input('api_token'))->first();
            if (!empty($user)) {
                auth()->login($user, true);
            }
        }
        $this->filterModel($request, $eService);

        return $this->sendResponse($eService->toArray(), 'EService retrieved successfully');
    }

    /**
     * Store a newly created EService in storage.
     *
     * @param CreateEServiceRequest $request
     *
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $this->validate($request, EService::$rules);
            $input = $request->all();
            // Get the service template
            $template = $this->serviceTemplateRepository->findWithoutFail($input['template_id'] ?? null);
            if($template){
                $input['name'] = $template->name ;
                $input['categories'] = [$template->category_id] ;
            }else{
                $input['categories'] = ($input['category_id'])? [$input['category_id']] : [];
            }

            $eService = $this->eServiceRepository->create($input);
            if (isset($input['image']) && $input['image'] && is_array($input['image'])) {
                foreach ($input['image'] as $fileUuid) {
                    $cacheUpload = $this->uploadRepository->getByUuid($fileUuid);
                    $mediaItem = $cacheUpload->getMedia('image')->first();
                    $mediaItem->copy($eService, 'image');
                }
            }
        } catch (ValidationException $e) {
            return $this->sendError(array_values($e->errors()),422);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse($eService->toArray(), __('lang.saved_successfully', ['operator' => __('lang.e_service')]));
    }

    /**
     * Update the specified EService in storage.
     *
     * @param int $id
     * @param UpdateEServiceRequest $request
     *
     * @return JsonResponse
     * @throws RepositoryException
     */
    public function update(int $id, UpdateEServiceRequest $request): JsonResponse
    {
        $this->eServiceRepository->pushCriteria(new EServicesOfUserCriteria(auth()->id()));
        $eService = $this->eServiceRepository->findWithoutFail($id);

        if (empty($eService)) {
            return $this->sendError('E Service not found');
        }
        try {
            $input = $request->all();
            $input['categories'] = $input['categories'] ?? [];
            $input['options_data'] = $input['options'] ?? [];
            unset($input['options']) ;
            $eService = $this->eServiceRepository->update($input, $id);
            if (isset($input['image']) && $input['image'] && is_array($input['image'])) {
                if ($eService->hasMedia('image')) {
                    $eService->getMedia('image')->each->delete();
                }
                foreach ($input['image'] as $fileUuid) {
                    $cacheUpload = $this->uploadRepository->getByUuid($fileUuid);
                    $mediaItem = $cacheUpload->getMedia('image')->first();
                    $mediaItem->copy($eService, 'image');
                }
            }
        } catch (Exception $e) {
             Log::error('FAIL:'. $e->getMessage() , [
                 'trace' => $e->getTraceAsString()
            ]);
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse($eService->toArray(), __('lang.updated_successfully', ['operator' => __('lang.e_service')]));
    }

    /**
     * Remove the specified EService from storage.
     *
     * @param int $id
     *
     * @return JsonResponse
     * @throws RepositoryException
     */
    public function destroy(int $id): JsonResponse
    {
        $this->eServiceRepository->pushCriteria(new EServicesOfUserCriteria(auth()->id()));
        $eService = $this->eServiceRepository->findWithoutFail($id);

        if (empty($eService)) {
            return $this->sendError('EService not found');
        }

        $eService = $this->eServiceRepository->delete($id);

        return $this->sendResponse($eService, __('lang.deleted_successfully', ['operator' => __('lang.e_service')]));

    }

    /**
     * Remove Media of EService
     * @param Request $request
     */
    public function removeMedia(Request $request): void
    {
        $input = $request->all();
        try {
            $this->eServiceRepository->pushCriteria(new EServicesOfUserCriteria(auth()->id()));
            $eService = $this->eServiceRepository->findWithoutFail($input['id']);
            if ($eService->hasMedia($input['collection'])) {
                $eService->getFirstMedia($input['collection'])->delete();
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }

    /**
     * Create a new EService from a ServiceTemplate
     * POST /eServices/from-template
     *
     * @param CreateEServiceFromTemplateRequest $request
     * @return JsonResponse
     */
    public function storeFromTemplate(CreateEServiceFromTemplateRequest $request): JsonResponse
    {
        try {
            // Request is automatically validated through FormRequest
            $salonId = $request->input('salon_id');
            $templateData = $request->except(['salon_id']);
            $eService = $this->eServiceFromTemplateService->create($templateData, $salonId);
            
            return $this->sendResponse(
                $eService->load('categories')->toArray(),
                __('lang.saved_successfully', ['operator' => __('lang.e_service')])
            );
        } catch (ValidationException $e) {
            return $this->sendError(array_values($e->errors()), 422);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    /**
     * Update an existing EService from template data
     * PUT /eServices/{id}/from-template
     *
     * @param int $id
     * @param UpdateEServiceFromTemplateRequest $request
     * @return JsonResponse
     */
    public function updateFromTemplate(int $id, UpdateEServiceFromTemplateRequest $request): JsonResponse
    {
        try {
            // Request is automatically validated through FormRequest
            $salonId = $request->input('salon_id');
            $templateData = $request->input('template');
            $eService = $this->eServiceFromTemplateService->update($id, $templateData, $salonId);

            return $this->sendResponse(
                $eService->toArray(),
                __('lang.updated_successfully', ['operator' => __('lang.e_service')])
            );
        } catch (ValidationException $e) {
            return $this->sendError(array_values($e->errors()), 422);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
}
