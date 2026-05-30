<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateServiceTemplateRequest;
use App\Http\Requests\UpdateServiceTemplateRequest;
use App\Models\ServiceTemplate;
use App\Repositories\ServiceTemplateRepository;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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
            $serviceTemplates = $this->serviceTemplateRepository->all();
            $serviceTemplates->loadMissing(['category', 'optionTemplates', 'media']);
            $this->applySearchFilters($request, $serviceTemplates);
            $this->limitOffset($request, $serviceTemplates);
            $this->filterCollection($request, $serviceTemplates);
        } catch (RepositoryException $e) {
            return $this->sendError($e->getMessage());
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }

        $payload = array_values(
            $serviceTemplates
                ->map(fn (ServiceTemplate $template) => $this->serializeTemplate($template))
                ->toArray()
        );

        return $this->sendResponse($payload, 'Service Templates retrieved successfully');
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
        
        $serviceTemplate->loadMissing(['category', 'optionTemplates', 'media']);
        $this->filterModel($request, $serviceTemplate);
        
        return $this->sendResponse($this->serializeTemplate($serviceTemplate), 'Service Template retrieved successfully');
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

    private function applySearchFilters(Request $request, Collection &$serviceTemplates): void
    {
        $keyword = trim((string) $request->input('keyword', ''));
        if ($keyword !== '') {
            $normalizedKeyword = Str::lower($keyword);
            $serviceTemplates = $serviceTemplates->filter(function (ServiceTemplate $template) use ($normalizedKeyword) {
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
            $serviceTemplates = $serviceTemplates->filter(function (ServiceTemplate $template) use ($normalizedCategoryIds) {
                return in_array((string) $template->category_id, $normalizedCategoryIds, true);
            });
        }

        $minPrice = $request->input('min_price');
        if ($minPrice !== null && $minPrice !== '') {
            $minPrice = (float) $minPrice;
            $serviceTemplates = $serviceTemplates->filter(function (ServiceTemplate $template) use ($minPrice) {
                $price = $this->resolveStartingPrice($template);
                return $price !== null && $price >= $minPrice;
            });
        }

        $maxPrice = $request->input('max_price');
        if ($maxPrice !== null && $maxPrice !== '') {
            $maxPrice = (float) $maxPrice;
            $serviceTemplates = $serviceTemplates->filter(function (ServiceTemplate $template) use ($maxPrice) {
                $price = $this->resolveStartingPrice($template);
                return $price !== null && $price <= $maxPrice;
            });
        }
    }

    private function resolveStartingPrice(ServiceTemplate $template): ?float
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

    private function serializeTemplate(ServiceTemplate $template): array
    {
        return [
            'id' => (string) $template->id,
            'name' => $template->name,
            'description' => $template->description,
            'category_id' => $template->category_id !== null ? (string) $template->category_id : null,
            'category' => $template->relationLoaded('category') && $template->category
                ? [
                    'id' => (string) $template->category->id,
                    'name' => $template->category->name,
                ]
                : null,
            'starting_price' => $this->resolveStartingPrice($template),
            'options_count' => $template->relationLoaded('optionTemplates')
                ? $template->optionTemplates->count()
                : $template->optionTemplates()->count(),
            'image_url' => $template->getFirstMediaUrl('image'),
            'source' => 'service_templates',
            'created_at' => $template->created_at,
            'updated_at' => $template->updated_at,
        ];
    }
}
