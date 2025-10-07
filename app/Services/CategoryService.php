<?php

namespace App\Services;

use App\Models\Category;
use App\Repositories\CategoryRepository;
use Illuminate\Support\Collection;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * Service de gestion et formatage des catégories
 * 
 * Ce service encapsule la logique de transformation et formatage des données :
 * - Arbres hiérarchiques de catégories
 * - Catégories avec leurs services (e_services)
 * - Structures de données optimisées pour l'API
 */
class CategoryService
{
    private CategoryRepository $categoryRepository;

    public function __construct(CategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * Retourne l'arbre complet des catégories (toutes les racines avec leurs enfants récursifs)
     * 
     * @param bool $withServices Inclure les services dans chaque catégorie
     * @param bool $onlyFeatured Uniquement les catégories featured
     * @return array
     */
    public function getCategoryTree(bool $withServices = false, bool $onlyFeatured = false): array
    {
        // Récupérer toutes les catégories racines
        $query = Category::roots()->with('media');
        
        if ($onlyFeatured) {
            $query->featured();
        }
        
        $rootCategories = $query->get();

        return $rootCategories->map(function ($category) use ($withServices) {
            return $this->buildCategoryNode($category, $withServices, true);
        })->toArray();
    }

    /**
     * Retourne une catégorie spécifique avec ses enfants directs
     * 
     * @param int $categoryId
     * @param bool $withServices Inclure les services
     * @return array
     */
    public function getCategoryWithChildren(int $categoryId, bool $withServices = false): array
    {
        $category = $this->findCategoryOrFail($categoryId);
        
        return $this->buildCategoryNode($category, $withServices, false);
    }

    /**
     * Retourne l'arbre complet d'une catégorie avec tous ses descendants et services
     * 
     * @param int $categoryId
     * @return array
     */
    public function getCategoryTreeWithServices(int $categoryId): array
    {
        $category = $this->findCategoryOrFail($categoryId);
        
        return $this->buildCategoryNode($category, true, true);
    }

    /**
     * Retourne une catégorie avec TOUS ses services (incluant ceux des descendants)
     * Format "plat" avec compteurs
     * 
     * @param int $categoryId
     * @return array
     */
    public function getCategoryWithServicesFlat(int $categoryId): array
    {
        $category = $this->findCategoryOrFail($categoryId);
        
        // Récupérer tous les descendants
        $descendantIds = $this->getDescendantIds($category);
        $allCategoryIds = array_merge([$categoryId], $descendantIds);
        
        // Récupérer tous les services liés à cette catégorie et ses descendants
        $services = \App\Models\EService::whereHas('categories', function ($query) use ($allCategoryIds) {
            $query->whereIn('categories.id', $allCategoryIds);
        })
        ->with(['salon', 'media', 'categories'])
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
            'has_children' => $category->hasChildren(),
            'children_count' => $descendantIds ? count($descendantIds) : 0,
            'services_count' => $services->count(),
            'services' => $services->map(function ($service) {
                return $this->formatService($service);
            })->toArray(),
        ];
    }

    /**
     * Retourne toutes les catégories racines avec leurs enfants directs
     * 
     * @param bool $withServices
     * @return array
     */
    public function getRootCategoriesWithChildren(bool $withServices = false): array
    {
        $rootCategories = Category::roots()
            ->with(['media', 'subCategories.media'])
            ->get();

        return $rootCategories->map(function ($category) use ($withServices) {
            return $this->buildCategoryNode($category, $withServices, false);
        })->toArray();
    }

    /**
     * Retourne le fil d'ariane d'une catégorie avec les services à chaque niveau
     * 
     * @param int $categoryId
     * @return array
     */
    public function getCategoryBreadcrumbWithServices(int $categoryId): array
    {
        $category = $this->findCategoryOrFail($categoryId);
        
        // Récupérer tous les IDs du chemin
        $pathIds = explode('/', $category->path);
        
        // Récupérer toutes les catégories du chemin
        $categories = Category::whereIn('id', $pathIds)
            ->orderByRaw("FIELD(id, " . implode(',', $pathIds) . ")")
            ->with(['media', 'eServices.media', 'eServices.salon'])
            ->get();

        return $categories->map(function ($cat) {
            return [
                'id' => $cat->id,
                'name' => $cat->name,
                'slug' => $cat->slug,
                'color' => $cat->color,
                'image' => $cat->getFirstMediaUrl('image'),
                'level' => $cat->level,
                'services_count' => $cat->eServices->count(),
                'services' => $cat->eServices->map(function ($service) {
                    return $this->formatService($service);
                })->toArray(),
            ];
        })->toArray();
    }

    /**
     * Retourne les catégories featured avec leurs services featured
     * 
     * @return array
     */
    public function getFeaturedCategoriesWithServices(): array
    {
        $categories = Category::featured()
            ->with(['media', 'featuredEServices.media', 'featuredEServices.salon'])
            ->get();

        return $categories->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'color' => $category->color,
                'image' => $category->getFirstMediaUrl('image'),
                'order' => $category->order,
                'services_count' => $category->featuredEServices->count(),
                'services' => $category->featuredEServices->map(function ($service) {
                    return $this->formatService($service);
                })->toArray(),
            ];
        })->toArray();
    }

    /**
     * Recherche de catégories avec leurs services
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
            $query->with(['eServices.media', 'eServices.salon']);
        }

        $categories = $query->get();

        return $categories->map(function ($category) use ($includeServices) {
            $data = [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'color' => $category->color,
                'image' => $category->getFirstMediaUrl('image'),
                'level' => $category->level,
                'breadcrumb' => $category->breadcrumb,
                'is_root' => $category->isRoot(),
            ];

            if ($includeServices) {
                $data['services_count'] = $category->eServices->count();
                $data['services'] = $category->eServices->map(function ($service) {
                    return $this->formatService($service);
                })->toArray();
            }

            return $data;
        })->toArray();
    }

    // ========================================
    // MÉTHODES PRIVÉES (HELPERS)
    // ========================================

    /**
     * Construit un nœud de catégorie avec ses enfants (récursif ou non)
     * 
     * @param Category $category
     * @param bool $withServices
     * @param bool $recursive Charger tous les descendants récursivement
     * @return array
     */
    private function buildCategoryNode(Category $category, bool $withServices = false, bool $recursive = false): array
    {
        $node = [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'color' => $category->color,
            'featured' => $category->featured,
            'order' => $category->order,
            'image' => $category->getFirstMediaUrl('image'),
            'level' => $category->level,
            'has_children' => $category->hasChildren(),
        ];

        // Ajouter les services si demandé
        if ($withServices) {
            $category->load(['eServices.media', 'eServices.salon']);
            $node['services_count'] = $category->eServices->count();
            $node['services'] = $category->eServices->map(function ($service) {
                return $this->formatService($service);
            })->toArray();
        }

        // Charger les enfants
        if ($category->hasChildren()) {
            $children = $category->subCategories()->with('media')->get();
            
            if ($recursive) {
                // Récursif : charger tous les descendants
                $node['children'] = $children->map(function ($child) use ($withServices, $recursive) {
                    return $this->buildCategoryNode($child, $withServices, $recursive);
                })->toArray();
            } else {
                // Non récursif : juste les enfants directs (format simple)
                $node['children'] = $children->map(function ($child) {
                    return [
                        'id' => $child->id,
                        'name' => $child->name,
                        'slug' => $child->slug,
                        'color' => $child->color,
                        'image' => $child->getFirstMediaUrl('image'),
                        'order' => $child->order,
                        'has_children' => $child->hasChildren(),
                    ];
                })->toArray();
            }
        } else {
            $node['children'] = [];
        }

        return $node;
    }

    /**
     * Formate un service pour l'API
     * 
     * @param \App\Models\EService $service
     * @return array
     */
    private function formatService($service): array
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
            'salon' => $service->salon ? [
                'id' => $service->salon->id,
                'name' => $service->salon->name,
            ] : null,
        ];
    }

    /**
     * Récupère tous les IDs des descendants d'une catégorie
     * 
     * @param Category $category
     * @return array
     */
    private function getDescendantIds(Category $category): array
    {
        return $category->descendants()->pluck('id')->toArray();
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
        
        if (empty($category)) {
            throw new ResourceNotFoundException("Catégorie avec l'ID {$categoryId} introuvable");
        }

        return $category;
    }
}