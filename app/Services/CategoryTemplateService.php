<?php

namespace App\Services;

use App\Models\Category;
use App\Models\ServiceTemplate;
use App\Repositories\CategoryRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * Service de gestion et formatage des catégories avec templates de service
 */
class CategoryTemplateService
{
    private CategoryRepository $categoryRepository;

    public function __construct(CategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * Arbre complet des catégories (toutes les racines avec descendants)
     *
     * @param bool $withTemplates Inclure les templates de service
     * @param bool $onlyFeatured Uniquement les catégories featured
     * @return array
     */
    public function getCategoryTree(bool $withTemplates = false, bool $onlyFeatured = false): array
    {
        $cacheKey = "category.template.tree.{$withTemplates}.{$onlyFeatured}";

        return Cache::remember($cacheKey, 3600, function () use ($withTemplates, $onlyFeatured) {
            // Charger toutes les catégories en une seule requête
            $query = Category::with('media');

            if ($withTemplates) {
                $query->with('serviceTemplates');
            }

            if ($onlyFeatured) {
                $query->where('featured', true);
            }

            $allCategories = $query->orderBy('order')->get();

            // Construire l'arbre en mémoire
            return $this->buildTree($allCategories, null, $withTemplates);
        });
    }

    /**
     * UNE catégorie spécifique avec ses enfants directs uniquement
     *
     * @param int $categoryId
     * @param bool $withTemplates
     * @return array
     */
    public function getCategoryWithChildren(int $categoryId, bool $withTemplates = false): array
    {
        $category = $this->findCategoryOrFail($categoryId);

        $category->load(['media', 'children.media']);

        if ($withTemplates) {
            $category->load(['serviceTemplates', 'children.serviceTemplates']);
        }

        $node = $this->categoryToTreeNode($category, $withTemplates, true);

        // Ajouter les enfants directs
        if ($category->relationLoaded('children')) {
            $node['children'] = $category->children->map(function ($child) use ($withTemplates) {
                return $this->categoryToTreeNode($child, $withTemplates, true);
            })->toArray();
        }

        return $node;
    }

    /**
     * UNE catégorie avec TOUS ses descendants et templates (récursif)
     *
     * @param int $categoryId
     * @return array
     */
    public function getCategoryTreeWithTemplates(int $categoryId): array
    {
        $cacheKey = "category.{$categoryId}.tree.templates";

        return Cache::remember($cacheKey, 1800, function () use ($categoryId) {
            $category = $this->findCategoryOrFail($categoryId);

            // Charger la catégorie avec ses templates
            $category->load(['media', 'serviceTemplates']);

            // Récupérer tous les descendants avec leurs templates
            $descendants = Category::where('path', 'like', $category->path . '/%')
                ->with(['media', 'serviceTemplates'])
                ->orderBy('path')
                ->get();

            // Combiner la catégorie et ses descendants
            $allCategories = collect([$category])->merge($descendants);

            // Construire l'arbre récursivement
            return $this->buildTreeFromCollection($allCategories, $category->id, true);
        });
    }


    /**
     * Retourne toutes les catégorie mise à plat - sans hiérarchie
     *
     * @param  $categories
     * @return array
     */
    function flattenCategoriesForAdminFront($categories, $prefix = '', $level = 0) : array
    {
        $list = [];
        foreach ($categories as $category) {
            $label = str_repeat('— ', $level) . $category['name'];
            // on ajoute la catégorie courante avec indentation
            $list[$category['id']] = [
                'label' => $label,
                'level' => $level,
            ];
            // s’il y a des enfants, on les parcourt récursivement
            if (count($category['children']) > 0) {
                $list += $this->flattenCategoriesForAdminFront($category['children'], $prefix . '— ' , $level + 1);
            }
        }

        return $list;
    }

    /**
     * Retourne toutes les catégorie mise à plat - sans hiérarchie
     *
     * @param  $categories
     * @return array
     */
    function flattenTemplatesForAdminFront($categories, $prefix = '', $level = 0) : array
    {
        $list = [];
        // dd($categories) ;
        foreach ($categories as $category) {
            $label = str_repeat('— ', $level) . $category['name'];
            // on ajoute la catégorie courante avec indentation
            $list[$category['id'].'*'] = [
                'label' => $label,
                'level' => $level,
            ];
            if(array_key_exists('templates' ,$category ) && count($category['templates']) > 0 ){
                foreach ($category['templates'] as $t) {
                    // on ajoute la catégorie courante avec indentation
                    $list[$t['id']] = [
                        'label' => str_repeat('* ', $level) .$t['name'],
                        
                    ];
                }
            }


            // s’il y a des enfants, on les parcourt récursivement
            if (count($category['children']) > 0) {
                $list += $this->flattenTemplatesForAdminFront($category['children'], $prefix . '— ' , $level + 1);
            }
        }

        return $list;
    }

    /**
     * UNE catégorie avec TOUS ses templates (plat - sans hiérarchie)
     *
     * @param int $categoryId
     * @return array
     */
    public function getCategoryWithTemplatesFlat(int $categoryId): array
    {
        $cacheKey = "category.{$categoryId}.templates.flat";

        return Cache::remember($cacheKey, 1800, function () use ($categoryId) {
            $category = $this->findCategoryOrFail($categoryId);

            // Utiliser le path pour trouver tous les descendants
            $allCategories = Category::where('id', $category->id)
                ->orWhere('path', 'like', $category->path . '/%')
                ->pluck('id');

            // Récupérer tous les templates
            $templates = ServiceTemplate::whereIn('category_id', $allCategories)
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
                'templates_count' => $templates->count(),
                'templates' => $templates->map(fn($t) => $this->formatTemplate($t))->toArray(),
            ];
        });
    }

    /**
     * Toutes les catégories racines avec leurs enfants directs
     *
     * @param bool $withTemplates
     * @return array
     */
    public function getRootCategoriesWithChildren(bool $withTemplates = false): array
    {
        $cacheKey = "category.template.roots.children.{$withTemplates}";

        // return Cache::remember($cacheKey, 300, function () use ($withTemplates) {
            $query = Category::roots()->with(['media', 'children.media']);

            if ($withTemplates) {
                $query->with(['serviceTemplates', 'children.serviceTemplates']);
            }

            return $query->get()->map(function($category) use ($withTemplates) {
                return $this->categoryToTreeNode($category, $withTemplates, true);
            })->toArray();
        // });
    }

    /**
     * Fil d'Ariane avec templates à chaque niveau
     *
     * @param int $categoryId
     * @return array
     */
    public function getCategoryBreadcrumbWithTemplates(int $categoryId): array
    {
        $category = $this->findCategoryOrFail($categoryId);

        $pathIds = explode('/', $category->path);

        return Category::whereIn('id', $pathIds)
            ->with(['media', 'serviceTemplates'])
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
                    'templates_count' => $cat->serviceTemplates->count(),
                    'templates' => $cat->serviceTemplates->map(fn($t) => $this->formatTemplate($t))->toArray(),
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Catégories featured avec leurs templates
     *
     * @return array
     */
    public function getFeaturedCategoriesWithTemplates(): array
    {
        return Cache::remember('categories.featured.templates', 3600, function () {
            return Category::featured()
                ->with(['media', 'serviceTemplates'])
                ->get()
                ->map(function ($category) {
                    $node = $this->categoryToTreeNode($category, false, false);
                    $node['templates_count'] = $category->serviceTemplates->count();
                    $node['templates'] = $category->serviceTemplates
                        ->map(fn($t) => $this->formatTemplate($t))
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
     * @param bool $includeTemplates
     * @return array
     */
    public function searchCategories(string $searchTerm, bool $includeTemplates = true): array
    {
        $query = Category::where('name', 'like', "%{$searchTerm}%")
            ->orWhere('description', 'like', "%{$searchTerm}%")
            ->with('media');

        if ($includeTemplates) {
            $query->with('serviceTemplates');
        }

        return $query->get()->map(function ($category) use ($includeTemplates) {
            $data = $this->categoryToTreeNode($category, $includeTemplates, true);
            $data['breadcrumb'] = $category->breadcrumb;
            $data['is_root'] = $category->isRoot();
            return $data;
        })->toArray();
    }

    /**
     * Toutes les catégories (tous niveaux) avec leurs descendants sous forme d'arbre
     * Retourne une structure plate avec chaque catégorie et son arbre de descendants
     *
     * @param bool $withTemplates Inclure les templates
     * @return array
     */
    public function getAllCategoriesWithDescendants(bool $withTemplates = false): array
    {
        $cacheKey = "category.template.all.descendants.{$withTemplates}";

        return Cache::remember($cacheKey, 3600, function () use ($withTemplates) {
            // Charger toutes les catégories
            $query = Category::with('media')->orderBy('path');

            if ($withTemplates) {
                $query->with('serviceTemplates');
            }

            $allCategories = $query->get();

            // Pour chaque catégorie, construire son arbre de descendants
            return $allCategories->map(function ($category) use ($allCategories, $withTemplates) {
                // Trouver tous les descendants de cette catégorie
                $descendants = $allCategories->filter(function ($cat) use ($category) {
                    return str_starts_with($cat->path, $category->path . '/');
                });

                $node = $this->categoryToTreeNode($category, $withTemplates, true);

                // Si la catégorie a des descendants, construire l'arbre
                if ($descendants->isNotEmpty()) {
                    $node['descendants_count'] = $descendants->count();
                    $node['descendants_tree'] = $this->buildTreeFromCollection(
                        collect([$category])->merge($descendants),
                        $category->id,
                        $withTemplates
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
     * Construit récursivement l'arbre à partir d'une collection
     *
     * @param Collection $categories
     * @param int|null $parentId
     * @param bool $withTemplates
     * @return array
     */
    private function buildTree($categories, ?int $parentId = null, bool $withTemplates = false): array
    {
        $branch = [];

        foreach ($categories as $category) {
            if ($category->parent_id === $parentId) {
                $node = $this->categoryToTreeNode($category, $withTemplates, true);

                // Récursion : chercher les enfants
                $children = $this->buildTree($categories, $category->id, $withTemplates);
                $node['children'] = $children;

                $branch[] = $node;
            }
        }

        return $branch;
    }

    /**
     * Construit un arbre récursif à partir d'une collection de catégories
     *
     * @param Collection $categories
     * @param int $categoryId
     * @param bool $withTemplates
     * @return array
     */
    private function buildTreeFromCollection(Collection $categories, int $categoryId, bool $withTemplates = false): array
    {
        $category = $categories->firstWhere('id', $categoryId);

        if (!$category) {
            return [];
        }

        $node = $this->categoryToTreeNode($category, $withTemplates, false);

        // Trouver les enfants directs
        $children = $categories->where('parent_id', $categoryId);

        if ($children->isNotEmpty()) {
            $node['children'] = $children->map(function ($child) use ($categories, $withTemplates) {
                return $this->buildTreeFromCollection($categories, $child->id, $withTemplates);
            })->values()->toArray();
        } else {
            $node['children'] = [];
        }

        return $node;
    }

    /**
     * Convertit la catégorie en nœud d'arbre avec templates
     *
     * @param Category $category
     * @param bool $withTemplates
     * @param bool $checkChildren Vérifier si a des enfants
     * @return array
     */
    private function categoryToTreeNode(Category $category, bool $withTemplates = false, bool $checkChildren = true): array
    {
        $node = [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'color' => $category->color,
            'description' => $category->description,
            'featured' => $category->featured,
            'order' => $category->order,
            'image' => $category->getFirstMediaUrl('image'),
            'level' => $category->level,
            'url' => $category->url,
        ];

        if ($checkChildren) {
            $node['has_children'] = $category->hasChildren();
            $node['children'] = $category->children->map(function($category) use ($withTemplates) {
                return $this->categoryToTreeNode($category, $withTemplates, true);
            })->toArray();
        }

        if ($withTemplates && $category->relationLoaded('serviceTemplates')) {
            $node['templates_count'] = $category->serviceTemplates->count();
            $node['templates'] = $category->serviceTemplates->map(function($template) {
                return $this->formatTemplate($template);
            })->toArray();
        }

        return $node;
    }

    /**
     * Formate un template pour l'API
     *
     * @param ServiceTemplate $template
     * @return array
     */
    private function formatTemplate(ServiceTemplate $template): array
    {
        return [
            'id' => $template->id,
            'name' => $template->name,
            'description' => $template->description,
            'category_id' => $template->category_id,
            'created_at' => $template->created_at,
            'updated_at' => $template->updated_at,
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