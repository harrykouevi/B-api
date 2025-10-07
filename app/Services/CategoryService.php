<?php

namespace App\Services;

use App\Models\Category;
use App\Models\EService;
use App\Repositories\CategoryRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * Service de gestion et formatage des catégories
 */
class CategoryService
{
    private CategoryRepository $categoryRepository;

    public function __construct(CategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * Arbre complet des catégories (toutes les racines avec descendants)
     *
     * @param bool $withServices Inclure les services
     * @param bool $onlyFeatured Uniquement les catégories featured
     * @return array
     */
    public function getCategoryTree(bool $withServices = false, bool $onlyFeatured = false): array
    {
        $cacheKey = "category.tree.{$withServices}.{$onlyFeatured}";

        return Cache::remember($cacheKey, 3600, static function () use ($withServices, $onlyFeatured) {
            return Category::getTreeWithDescendants($withServices, $onlyFeatured);
        });
    }

    /**
     * UNE catégorie spécifique avec ses enfants directs uniquement
     *
     * @param int $categoryId
     * @param bool $withServices
     * @return array
     */
    public function getCategoryWithChildren(int $categoryId, bool $withServices = false): array
    {
        $category = $this->findCategoryOrFail($categoryId);

        $category->load(['media', 'children.media']);

        if ($withServices) {
            $category->load(['eServices.media', 'eServices.salon', 'children.eServices']);
        }

        $node = $category->toTreeNode($withServices, true);

        // Ajouter les enfants directs
        if ($category->relationLoaded('children')) {
            $node['children'] = $category->children->map(function ($child) use ($withServices) {
                return $child->toTreeNode($withServices, true);
            })->toArray();
        }

        return $node;
    }

    /**
     * UNE catégorie avec TOUS ses descendants et services (récursif)
     *
     * @param int $categoryId
     * @return array
     */
    public function getCategoryTreeWithServices(int $categoryId): array
    {
        $cacheKey = "category.{$categoryId}.tree.services";

        return Cache::remember($cacheKey, 1800, function () use ($categoryId) {
            $category = $this->findCategoryOrFail($categoryId);

            // Charger la catégorie avec ses services
            $category->load(['media', 'eServices.media', 'eServices.salon']);

            // Récupérer tous les descendants avec leurs services
            $descendants = Category::where('path', 'like', $category->path . '/%')
                ->with(['media', 'eServices.media', 'eServices.salon'])
                ->orderBy('path')
                ->get();

            // Combiner la catégorie et ses descendants
            $allCategories = collect([$category])->merge($descendants);

            // Construire l'arbre récursivement
            return $this->buildTreeFromCollection($allCategories, $category->id, true);
        });
    }

    /**
     * 4️⃣ UNE catégorie avec TOUS ses services (plat - sans hiérarchie)
     *
     * @param int $categoryId
     * @return array
     */
    public function getCategoryWithServicesFlat(int $categoryId): array
    {
        $cacheKey = "category.{$categoryId}.services.flat";

        return Cache::remember($cacheKey, 1800, function () use ($categoryId) {
            $category = $this->findCategoryOrFail($categoryId);

            // Utiliser le path pour trouver tous les descendants
            $allCategories = Category::where('id', $category->id)
                ->orWhere('path', 'like', $category->path . '/%')
                ->pluck('id');

            // Récupérer tous les services
            $services = EService::whereHas('categories', static function ($query) use ($allCategories) {
                $query->whereIn('categories.id', $allCategories);
            })
                ->with(['salon:id,name', 'media'])
                ->get();

            return [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'color' => $category->color,
                'featured' => $category->featured,
                'image' => $category->getFirstMediaUrl('image'),
                'level' => $category->level,
                'breadcrumb' => $category->breadcrumb,
                'url' => $category->url,
                'has_children' => $category->hasChildren(),
                'children_count' => $allCategories->count() - 1,
                'services_count' => $services->count(),
                'services' => $services->map(fn($s) => $this->formatService($s))->toArray(),
            ];
        });
    }

    /**
     * Toutes les catégories racines avec leurs enfants directs
     *
     * @param bool $withServices
     * @return array
     */
    public function getRootCategoriesWithChildren(bool $withServices = false): array
    {
        $cacheKey = "category.roots.children.{$withServices}";

        return Cache::remember($cacheKey, 3600, static function () use ($withServices) {
            return Category::getRootsWithChildren($withServices);
        });
    }

    /**
     *  Fil d'Ariane avec services à chaque niveau
     *
     * @param int $categoryId
     * @return array
     */
    public function getCategoryBreadcrumbWithServices(int $categoryId): array
    {
        $category = $this->findCategoryOrFail($categoryId);

        $pathIds = explode('/', $category->path);

        return Category::whereIn('id', $pathIds)
            ->with(['media', 'eServices.media', 'eServices.salon'])
            ->get()
            ->sortBy('path')
            ->map(function ($cat) {
                return [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'slug' => $cat->slug,
                    'color' => $cat->color,
                    'image' => $cat->getFirstMediaUrl('image'),
                    'level' => $cat->level,
                    'url' => $cat->url,
                    'services_count' => $cat->eServices->count(),
                    'services' => $cat->eServices->map(fn($s) => $this->formatService($s))->toArray(),
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Catégories featured avec leurs services featured
     *
     * @return array
     */
    public function getFeaturedCategoriesWithServices(): array
    {
        return Cache::remember('categories.featured.services', 3600, function () {
            return Category::featured()
                ->with(['media', 'featuredEServices.media', 'featuredEServices.salon'])
                ->get()
                ->map(function ($category) {
                    $node = $category->toTreeNode(false, false);
                    $node['services_count'] = $category->featuredEServices->count();
                    $node['services'] = $category->featuredEServices
                        ->map(fn($s) => $this->formatService($s))
                        ->toArray();
                    return $node;
                })
                ->toArray();
        });
    }

    /**
     * Recherche de catégories
     *
     * @param string $searchTerm
     * @param bool $includeServices
     * @return array
     */
    public function searchCategories(string $searchTerm, bool $includeServices = true): array
    {
        $query = Category::where('name', 'like', "%{$searchTerm}%")
            ->orWhere('description', 'like', "%{$searchTerm}%")
            ->with('media');

        if ($includeServices) {
            $query->with(['eServices.media', 'eServices.salon:id,name']);
        }

        return $query->get()->map(function ($category) use ($includeServices) {
            $data = $category->toTreeNode($includeServices, true);
            $data['breadcrumb'] = $category->breadcrumb;
            $data['is_root'] = $category->isRoot();
            return $data;
        })->toArray();
    }

    /**
     * Toutes les catégories (tous niveaux) avec leurs descendants sous forme d'arbre
     * Retourne une structure plate avec chaque catégorie et son arbre de descendants
     *
     * @param bool $withServices Inclure les services
     * @return array
     */
    public function getAllCategoriesWithDescendants(bool $withServices = false): array
    {
        $cacheKey = "category.all.descendants.{$withServices}";

        return Cache::remember($cacheKey, 3600, function () use ($withServices) {
            // Charger toutes les catégories
            $query = Category::with('media')->orderBy('path');

            if ($withServices) {
                $query->with(['eServices.media', 'eServices.salon']);
            }

            $allCategories = $query->get();

            // Pour chaque catégorie, construire son arbre de descendants
            return $allCategories->map(function ($category) use ($allCategories, $withServices) {
                // Trouver tous les descendants de cette catégorie
                $descendants = $allCategories->filter(function ($cat) use ($category) {
                    return str_starts_with($cat->path, $category->path . '/');
                });

                $node = $category->toTreeNode($withServices, true);

                // Si la catégorie a des descendants, construire l'arbre
                if ($descendants->isNotEmpty()) {
                    $node['descendants_count'] = $descendants->count();
                    $node['descendants_tree'] = $this->buildTreeFromCollection(
                        collect([$category])->merge($descendants),
                        $category->id,
                        $withServices
                    )['children'] ?? [];
                } else {
                    $node['descendants_count'] = 0;
                    $node['descendants_tree'] = [];
                }

                // Ajouter des infos supplémentaires
                $node['breadcrumb'] = $category->breadcrumb;
                $node['is_root'] = $category->isRoot();
                $node['parent_id'] = $category->parent_id;

                return $node;
            })->toArray();
        });
    }

    // ========================================
    // MÉTHODES PRIVÉES (HELPERS)
    // ========================================

    /**
     * Construit un arbre récursif à partir d'une collection de catégories
     *
     * @param Collection $categories
     * @param int $categoryId
     * @param bool $withServices
     * @return array
     */
    private function buildTreeFromCollection(Collection $categories, int $categoryId, bool $withServices = false): array
    {
        $category = $categories->firstWhere('id', $categoryId);

        if (!$category) {
            return [];
        }

        $node = $category->toTreeNode($withServices, false);

        // Trouver les enfants directs
        $children = $categories->where('parent_id', $categoryId);

        if ($children->isNotEmpty()) {
            $node['children'] = $children->map(function ($child) use ($categories, $withServices) {
                return $this->buildTreeFromCollection($categories, $child->id, $withServices);
            })->values()->toArray();
        } else {
            $node['children'] = [];
        }

        return $node;
    }

    /**
     * Formate un service pour l'API
     *
     * @param EService $service
     * @return array
     */
    private function formatService(EService $service): array
    {
        return [
            'id' => $service->id,
            'name' => $service->name,
            'description' => $service->description,
            'price' => $service->price,
            'discount_price' => $service->discount_price,
            'final_price' => $service->getPrice(),
            'has_discount' => $service->hasDiscount(),
            'duration' => $service->duration,
            'featured' => $service->featured,
            'available' => $service->available,
            'image' => $service->getFirstMediaUrl('image'),
            'salon' => $service->relationLoaded('salon') && $service->salon ? [
                'id' => $service->salon->id,
                'name' => $service->salon->name,
            ] : null,
        ];
    }

    /**
     * Trouve une catégorie ou lance une exception
     *
     * @param int $categoryId
     * @return Category
     * @throws ResourceNotFoundException
     */
    private function findCategoryOrFail(int $categoryId): Category
    {
        $category = $this->categoryRepository->findWithoutFail($categoryId);

        if ($category === null) {
            throw new ResourceNotFoundException("Catégorie avec l'ID {$categoryId} introuvable");
        }

        return $category;
    }
}