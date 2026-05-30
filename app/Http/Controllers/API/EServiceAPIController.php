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
use App\Models\ServiceTemplate;
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
use Illuminate\Support\Str;
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
        EServiceFromTemplateService $eServiceFromTemplateService,
        ServiceTemplateRepository $serviceTemplateRepository
    ) {
        parent::__construct();
        $this->eServiceRepository = $eServiceRepo;
        $this->userRepository = $userRepository;
        $this->uploadRepository = $uploadRepository;
        $this->eServiceFromTemplateService = $eServiceFromTemplateService;
        $this->serviceTemplateRepository = $serviceTemplateRepository;
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
            $eServices = $this->getSearchableEServices($request);
            $this->limitOffset($request, $eServices);
            $this->filterCollection($request, $eServices);
            $eServices = array_values($eServices->toArray());
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendResponse($eServices, 'E Services retrieved successfully');
    }

    public function searchCatalog(Request $request): JsonResponse
    {
        try {
            $eServices = $this->getSearchableEServices($request);
            $templates = $this->getSearchableServiceTemplates($request);

            $results = $this->buildCatalogResults($eServices, $templates);
            $this->limitOffset($request, $results);

            return $this->sendResponse(
                array_values($results->toArray()),
                'Catalog search retrieved successfully'
            );
        } catch (Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }

    private function getSearchableEServices(Request $request): Collection
    {
        $this->eServiceRepository->pushCriteria(new RequestCriteria($request));
        $this->eServiceRepository->pushCriteria(new EServicesOfUserCriteria(auth()->id()));
        $this->eServiceRepository->pushCriteria(new NearCriteria($request));

        $eServices = $this->eServiceRepository->all();
        $eServices->loadMissing(['categories', 'salon.address', 'media']);

        $this->availableEServices($eServices);
        $this->availableSalon($request, $eServices);
        $this->hasValidSubscription($request, $eServices);
        $this->applySearchFilters($request, $eServices);

        return $eServices->values();
    }

    private function getSearchableServiceTemplates(Request $request): Collection
    {
        $this->serviceTemplateRepository->pushCriteria(new RequestCriteria($request));

        $templates = $this->serviceTemplateRepository->all();
        $templates->loadMissing(['category', 'optionTemplates', 'media']);

        $this->applyTemplateSearchFilters($request, $templates);

        return $templates->values();
    }

    private function buildCatalogResults(Collection $eServices, Collection $templates): Collection
    {
        $serviceResults = $eServices->map(function (EService $service) {
            $category = $service->categories->first();

            return [
                'result_id' => 'e_service_' . $service->id,
                'source' => 'e_service',
                'name' => $service->name,
                'description' => $service->description,
                'category_id' => $category ? (string) $category->id : null,
                'category_name' => $category ? $category->name : null,
                'display_price' => $this->resolveEffectivePrice($service),
                'image_url' => $service->getFirstMediaUrl('image'),
                'salon_name' => optional($service->salon)->name,
                'e_service' => $service->toArray(),
                'template' => null,
            ];
        });

        $templateResults = $templates->map(function (ServiceTemplate $template) {
            return [
                'result_id' => 'service_template_' . $template->id,
                'source' => 'service_template',
                'name' => $template->name,
                'description' => $template->description,
                'category_id' => $template->category_id !== null ? (string) $template->category_id : null,
                'category_name' => optional($template->category)->name,
                'display_price' => $this->resolveTemplateStartingPrice($template),
                'image_url' => $template->getFirstMediaUrl('image'),
                'salon_name' => null,
                'e_service' => null,
                'template' => $this->serializeTemplateForSearch($template),
            ];
        });

        return $serviceResults->concat($templateResults)->values();
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
     * Apply explicit search filters for catalog browsing.
     */
    private function applySearchFilters(Request $request, Collection &$eServices): void
    {
        $keyword = trim((string) $request->input('keyword', ''));
        if ($keyword !== '') {
            $normalizedKeyword = Str::lower($keyword);
            $eServices = $eServices->filter(function ($element) use ($normalizedKeyword) {
                $name = $element->name;
                if (is_array($name)) {
                    $name = implode(' ', array_filter($name));
                }

                return Str::contains(Str::lower((string) $name), $normalizedKeyword);
            });
        }

        $categoryIds = $request->input('category_ids', []);
        if (is_string($categoryIds)) {
            $categoryIds = array_filter(explode(',', $categoryIds));
        }
        if (is_array($categoryIds) && count($categoryIds) > 0) {
            $normalizedCategoryIds = array_map('strval', $categoryIds);
            $eServices = $eServices->filter(function ($element) use ($normalizedCategoryIds) {
                return $element->categories->contains(function ($category) use ($normalizedCategoryIds) {
                    return in_array((string) $category->id, $normalizedCategoryIds, true);
                });
            });
        }

        $minPrice = $request->input('min_price');
        if ($minPrice !== null && $minPrice !== '') {
            $minPrice = (float) $minPrice;
            $eServices = $eServices->filter(function ($element) use ($minPrice) {
                return $this->resolveEffectivePrice($element) >= $minPrice;
            });
        }

        $maxPrice = $request->input('max_price');
        if ($maxPrice !== null && $maxPrice !== '') {
            $maxPrice = (float) $maxPrice;
            $eServices = $eServices->filter(function ($element) use ($maxPrice) {
                return $this->resolveEffectivePrice($element) <= $maxPrice;
            });
        }
    }

    private function resolveEffectivePrice(EService $eService): float
    {
        $discountPrice = (float) ($eService->discount_price ?? 0);
        if ($discountPrice > 0) {
            return $discountPrice;
        }

        return (float) ($eService->price ?? 0);
    }

    private function applyTemplateSearchFilters(Request $request, Collection &$templates): void
    {
        $keyword = trim((string) $request->input('keyword', ''));
        if ($keyword !== '') {
            $normalizedKeyword = Str::lower($keyword);
            $templates = $templates->filter(function (ServiceTemplate $template) use ($normalizedKeyword) {
                $haystacks = [
                    Str::lower((string) $template->name),
                    Str::lower((string) $template->description),
                    Str::lower((string) optional($template->category)->name),
                ];

                foreach ($haystacks as $haystack) {
                    if ($haystack !== '' && Str::contains($haystack, $normalizedKeyword)) {
                        return true;
                    }
                }

                return false;
            });
        }

        $categoryIds = $request->input('category_ids', []);
        if (is_string($categoryIds)) {
            $categoryIds = array_filter(explode(',', $categoryIds));
        }
        if (is_array($categoryIds) && count($categoryIds) > 0) {
            $normalizedCategoryIds = array_map('strval', $categoryIds);
            $templates = $templates->filter(function (ServiceTemplate $template) use ($normalizedCategoryIds) {
                return in_array((string) $template->category_id, $normalizedCategoryIds, true);
            });
        }

        $minPrice = $request->input('min_price');
        if ($minPrice !== null && $minPrice !== '') {
            $minPrice = (float) $minPrice;
            $templates = $templates->filter(function (ServiceTemplate $template) use ($minPrice) {
                $price = $this->resolveTemplateStartingPrice($template);
                return $price !== null && $price >= $minPrice;
            });
        }

        $maxPrice = $request->input('max_price');
        if ($maxPrice !== null && $maxPrice !== '') {
            $maxPrice = (float) $maxPrice;
            $templates = $templates->filter(function (ServiceTemplate $template) use ($maxPrice) {
                $price = $this->resolveTemplateStartingPrice($template);
                return $price !== null && $price <= $maxPrice;
            });
        }
    }

    private function resolveTemplateStartingPrice(ServiceTemplate $template): ?float
    {
        if (isset($template->price) && is_numeric($template->price)) {
            $price = (float) $template->price;
            if ($price > 0) {
                return $price;
            }
        }

        $prices = $template->relationLoaded('optionTemplates')
            ? $template->optionTemplates
                ->pluck('price')
                ->filter(fn ($value) => is_numeric($value) && (float) $value > 0)
            : collect();

        if ($prices->isEmpty()) {
            $minPrice = $template->optionTemplates()->where('price', '>', 0)->min('price');
            return $minPrice !== null ? (float) $minPrice : null;
        }

        return (float) $prices->min();
    }

    private function serializeTemplateForSearch(ServiceTemplate $template): array
    {
        return [
            'id' => (string) $template->id,
            'name' => $template->name,
            'description' => $template->description,
            'category_id' => $template->category_id !== null ? (string) $template->category_id : null,
            'category' => $template->category
                ? [
                    'id' => (string) $template->category->id,
                    'name' => $template->category->name,
                ]
                : null,
            'starting_price' => $this->resolveTemplateStartingPrice($template),
            'options_count' => $template->relationLoaded('optionTemplates')
                ? $template->optionTemplates->count()
                : $template->optionTemplates()->count(),
            'image_url' => $template->getFirstMediaUrl('image'),
            'source' => 'service_template',
        ];
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
