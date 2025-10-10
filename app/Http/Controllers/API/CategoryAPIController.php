<?php
/*
 * File name: CategoryAPIController.php
 * Last modified: 2024.04.10 at 12:26:06
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Http\Controllers\API;


use App\Criteria\Categories\NearCriteria;
use App\Criteria\Categories\ParentCriteria;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateCategoryRequest;
use App\Repositories\UploadRepository;
use App\Repositories\CustomFieldRepository;

use App\Models\Category;
use App\Repositories\CategoryRepository;
use App\Services\CategoryService;
use App\Services\CategoryTemplateService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InfyOm\Generator\Criteria\LimitOffsetCriteria;
use Prettus\Repository\Criteria\RequestCriteria;
use Prettus\Repository\Exceptions\RepositoryException;


/**
 * Class CategoryController
 * @package App\Http\Controllers\API
 */
class CategoryAPIController extends Controller
{
    /** @var  CategoryRepository */
    private CategoryRepository $categoryRepository;
    /**
     * @var UploadRepository
     */
    private UploadRepository $uploadRepository;
    private CustomFieldRepository $customFieldRepository;
    private CategoryService $categoryService;
    private CategoryTemplateService $categoryTemplateService;

    public function __construct(
        CategoryRepository $categoryRepo,
        CustomFieldRepository $customFieldRepo,
        UploadRepository $uploadRepo,
        CategoryService $categoryService,
        CategoryTemplateService $categoryTemplateService
    )
    {
        $this->categoryRepository = $categoryRepo;
        $this->uploadRepository = $uploadRepo;
        $this->customFieldRepository = $customFieldRepo;
        $this->categoryService = $categoryService;
        $this->categoryTemplateService = $categoryTemplateService;
        parent::__construct();
    }

    /**
     * Display a listing of the Category.
     * GET|HEAD /categories
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $this->categoryRepository->pushCriteria(new RequestCriteria($request));
            $this->categoryRepository->pushCriteria(new ParentCriteria($request));
            $this->categoryRepository->pushCriteria(new NearCriteria($request));
            $this->categoryRepository->pushCriteria(new LimitOffsetCriteria($request));
        } catch (RepositoryException $e) {
            return $this->sendError($e->getMessage());
        }
        $categories = $this->categoryRepository->all();

        return $this->sendResponse($categories->toArray(), 'Categories retrieved successfully');
    }

    /**
     * Display the specified Category.
     * GET|HEAD /categories/{id}
     *
     * @param int $id
     *
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        /** @var Category $category */
        if (!empty($this->categoryRepository)) {
            $category = $this->categoryRepository->findWithoutFail($id);
        }

        if (empty($category)) {
            return $this->sendError('Category not found');
        }

        return $this->sendResponse($category->toArray(), 'Category retrieved successfully');
    }


     /**
     * Store a newly created Category in storage.
     *
     * @param CreateCategoryRequest $request
     *
     * @return JsonResponse
     */
    public function store(CreateCategoryRequest $request): JsonResponse
    {
        $input = $request->all();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->categoryRepository->model());
        try {
            $category = $this->categoryRepository->create($input);
            $category->customFieldsValues()->createMany(getCustomFieldsValues($customFields, $request));
            if (isset($input['image']) && $input['image']) {
                $cacheUpload = $this->uploadRepository->getByUuid($input['image']);
                $mediaItem = $cacheUpload->getMedia('image')->first();
                // $mediaItem = $mediaItem->forgetCustomProperty('generated_conversions');
                $mediaItem->copy($category, 'image');
            }
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse($category->toArray(), __('lang.saved_successfully', ['operator' => __('lang.e_service')]));
    }

    // ========================================
    // NOUVELLES MÉTHODES AVEC CategoryService
    // ========================================

    /**
     * Arbre complet des catégories (toutes les racines avec descendants)
     * GET /categories/tree
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function tree(Request $request): JsonResponse
    {
        try {
            $withServices = $request->boolean('with_services', false);
            $onlyFeatured = $request->boolean('featured', false);

            $tree = $this->categoryService->getCategoryTree($withServices, $onlyFeatured);

            return $this->sendResponse($tree, 'Category tree retrieved successfully');
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    /**
     * Toutes les catégories racines avec leurs enfants directs
     * GET /categories/roots
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function roots(Request $request): JsonResponse
    {
        try {
            $withServices = $request->boolean('with_services', false);

            $roots = $this->categoryService->getRootCategoriesWithChildren($withServices);

            return $this->sendResponse($roots, 'Root categories retrieved successfully');
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    /**
     * Catégories featured avec leurs services featured
     * GET /categories/featured
     *
     * @return JsonResponse
     */
    public function featured(): JsonResponse
    {
        try {
            $featured = $this->categoryService->getFeaturedCategoriesWithServices();

            return $this->sendResponse($featured, 'Featured categories retrieved successfully');
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    /**
     * Recherche de catégories
     * GET /categories/search
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $searchTerm = $request->input('q', '');

            if (empty($searchTerm)) {
                return $this->sendError('Search term is required');
            }

            $includeServices = $request->boolean('with_services', true);

            $results = $this->categoryService->searchCategories($searchTerm, $includeServices);

            return $this->sendResponse($results, 'Search results retrieved successfully');
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    /**
     * UNE catégorie spécifique avec ses enfants directs uniquement
     * GET /categories/{id}/children
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function children(int $id, Request $request): JsonResponse
    {
        try {
            $withServices = $request->boolean('with_services', false);

            $category = $this->categoryService->getCategoryWithChildren($id, $withServices);

            return $this->sendResponse($category, 'Category with children retrieved successfully');
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    /**
     * UNE catégorie avec TOUS ses descendants et services (récursif)
     * GET /categories/{id}/tree-with-services
     *
     * @param int $id
     * @return JsonResponse
     */
    public function treeWithServices(int $id): JsonResponse
    {
        try {
            $tree = $this->categoryService->getCategoryTreeWithServices($id);

            return $this->sendResponse($tree, 'Category tree with services retrieved successfully');
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    /**
     * UNE catégorie avec TOUS ses services (plat - sans hiérarchie)
     * GET /categories/{id}/services
     *
     * @param int $id
     * @return JsonResponse
     */
    public function services(int $id): JsonResponse
    {
        try {
            $data = $this->categoryService->getCategoryWithServicesFlat($id);

            return $this->sendResponse($data, 'Category services retrieved successfully');
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    /**
     * Fil d'Ariane avec services à chaque niveau
     * GET /categories/{id}/breadcrumb
     *
     * @param int $id
     * @return JsonResponse
     */
    public function breadcrumb(int $id): JsonResponse
    {
        try {
            $breadcrumb = $this->categoryService->getCategoryBreadcrumbWithServices($id);

            return $this->sendResponse($breadcrumb, 'Category breadcrumb retrieved successfully');
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    /**
     * Toutes les catégories (tous niveaux) avec leurs descendants sous forme d'arbre
     * GET /categories/all-with-descendants
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function allWithDescendants(Request $request): JsonResponse
    {
        try {
            $withServices = $request->boolean('with_services', false);

            $categories = $this->categoryService->getAllCategoriesWithDescendants($withServices);

            return $this->sendResponse($categories, 'All categories with descendants retrieved successfully');
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    // ========================================
    // ENDPOINTS POUR LES TEMPLATES DE SERVICE
    // ========================================

    /**
     * Arbre complet des catégories avec templates (toutes les racines avec descendants)
     * GET /categories/templates/tree
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function templatesTree(Request $request): JsonResponse
    {
        try {
            $withTemplates = $request->boolean('with_templates', false);
            $onlyFeatured = $request->boolean('featured', false);

            $tree = $this->categoryTemplateService->getCategoryTree($withTemplates, $onlyFeatured);

            return $this->sendResponse($tree, 'Category tree with templates retrieved successfully');
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    /**
     * Toutes les catégories racines avec leurs enfants directs et templates
     * GET /categories/templates/roots
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function templatesRoots(Request $request): JsonResponse
    {
        try {
            $withTemplates = $request->boolean('with_templates', false);

            $roots = $this->categoryTemplateService->getRootCategoriesWithChildren($withTemplates);

            return $this->sendResponse($roots, 'Root categories with templates retrieved successfully');
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    /**
     * Catégories featured avec leurs templates
     * GET /categories/templates/featured
     *
     * @return JsonResponse
     */
    public function templatesFeatured(): JsonResponse
    {
        try {
            $featured = $this->categoryTemplateService->getFeaturedCategoriesWithTemplates();

            return $this->sendResponse($featured, 'Featured categories with templates retrieved successfully');
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    /**
     * Recherche de catégories avec templates
     * GET /categories/templates/search
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function templatesSearch(Request $request): JsonResponse
    {
        try {
            $searchTerm = $request->input('q', '');

            if (empty($searchTerm)) {
                return $this->sendError('Search term is required');
            }

            $includeTemplates = $request->boolean('with_templates', true);

            $results = $this->categoryTemplateService->searchCategories($searchTerm, $includeTemplates);

            return $this->sendResponse($results, 'Template search results retrieved successfully');
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    /**
     * UNE catégorie spécifique avec ses enfants directs et templates
     * GET /categories/{id}/templates/children
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function templatesChildren(int $id, Request $request): JsonResponse
    {
        try {
            $withTemplates = $request->boolean('with_templates', false);

            $category = $this->categoryTemplateService->getCategoryWithChildren($id, $withTemplates);

            return $this->sendResponse($category, 'Category with children and templates retrieved successfully');
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    /**
     * UNE catégorie avec TOUS ses descendants et templates (récursif)
     * GET /categories/{id}/templates/tree
     *
     * @param int $id
     * @return JsonResponse
     */
    public function templatesTreeWithTemplates(int $id): JsonResponse
    {
        try {
            $tree = $this->categoryTemplateService->getCategoryTreeWithTemplates($id);

            return $this->sendResponse($tree, 'Category tree with templates retrieved successfully');
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    /**
     * UNE catégorie avec TOUS ses templates (plat - sans hiérarchie)
     * GET /categories/{id}/templates
     *
     * @param int $id
     * @return JsonResponse
     */
    public function templates(int $id): JsonResponse
    {
        try {
            $data = $this->categoryTemplateService->getCategoryWithTemplatesFlat($id);

            return $this->sendResponse($data, 'Category templates retrieved successfully');
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    /**
     * Fil d'Ariane avec templates à chaque niveau
     * GET /categories/{id}/templates/breadcrumb
     *
     * @param int $id
     * @return JsonResponse
     */
    public function templatesBreadcrumb(int $id): JsonResponse
    {
        try {
            $breadcrumb = $this->categoryTemplateService->getCategoryBreadcrumbWithTemplates($id);

            return $this->sendResponse($breadcrumb, 'Category breadcrumb with templates retrieved successfully');
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    /**
     * Toutes les catégories (tous niveaux) avec leurs descendants et templates sous forme d'arbre
     * GET /categories/templates/all-with-descendants
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function templatesAllWithDescendants(Request $request): JsonResponse
    {
        try {
            $withTemplates = $request->boolean('with_templates', false);

            $categories = $this->categoryTemplateService->getAllCategoriesWithDescendants($withTemplates);

            return $this->sendResponse($categories, 'All categories with descendants and templates retrieved successfully');
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
}
