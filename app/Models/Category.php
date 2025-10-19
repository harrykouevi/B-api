<?php

namespace App\Models;

use App\Observers\CategoryObserver;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use App\Models\CustomFieldValue;
use App\Models\Discountable;

/**
 * Class Category
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $color
 * @property string|null $description
 * @property int $order
 * @property bool $featured
 * @property int|null $parent_id
 * @property string|null $path
 * @property string|null $path_slugs
 * @property string|null $path_names
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read Category|null $parent
 * @property-read Collection|Category[] $children
 * @property-read Collection|EService[] $eServices
 * @property-read array $breadcrumb
 * @property-read string $url
 * @property-read int $level
 *
 * @method static Builder|Category roots()
 * @method static Builder|Category featured()
 * @method static factory()
 */
#[ObservedBy([CategoryObserver::class])]
class Category extends Model implements HasMedia
{
    use InteractsWithMedia {
        getFirstMediaUrl as protected getFirstMediaUrlTrait;
    }

    // ========================================
    // CONFIGURATION
    // ========================================

    public $table = 'categories';

    public $fillable = [
        'name',
        'slug',
        'color',
        'description',
        'featured',
        'order',
        'parent_id',
        'path',
        'path_slugs',
        'path_names',
    ];

    protected $casts = [
        'name' => 'string',
        'slug' => 'string',
        'color' => 'string',
        'description' => 'string',
        'featured' => 'boolean',
        'order' => 'integer',
        'parent_id' => 'integer',
        'path' => 'string',
        'path_slugs' => 'string',
        'path_names' => 'string',
    ];

    protected $appends = ['custom_fields', 'has_media'];

    protected $hidden = ["created_at", "updated_at"];

    /**
     * Temporary properties for observer (not in database)
     */
    public ?string $oldPath = null;
    public ?string $oldPathSlugs = null;
    public ?string $oldPathNames = null;

    /**
     * Validation rules
     */
    public static array $rules = [
        'name' => 'required|max:127',
        'color' => 'nullable|max:36',
        'description' => 'nullable',
        'order' => 'nullable|numeric|min:0',
        'parent_id' => 'nullable|exists:categories,id'
    ];

    // ========================================
    // RELATIONS
    // ========================================

    /**
     * Parent category
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Child categories
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('order');
    }

    /**
     * All descendants (using path)
     */
    public function descendants(): Builder
    {
        return self::where('path', 'like', $this->path . '/%');
    }

    /**
     * Services in this category
     */
    public function eServices(): BelongsToMany
    {
        return $this->belongsToMany(EService::class, 'e_service_categories');
    }

    /**
     * Featured services in this category
     */
    public function featuredEServices(): BelongsToMany
    {
        return $this->belongsToMany(EService::class, 'e_service_categories')
            ->where('e_services.featured', true);
    }

    public function discountables(): MorphMany
    {
        return $this->morphMany(Discountable::class, 'discountable');
    }

    public function serviceTemplates(): HasMany
    {
        return $this->hasMany(ServiceTemplate::class);
    }

    public function customFieldsValues(): MorphMany
    {
        return $this->morphMany(CustomFieldValue::class, 'customizable');
    }

    // ========================================
    // ACCESSORS (ATTRIBUTES)
    // ========================================

    /**
     * Get breadcrumb array
     */
    public function getBreadcrumbAttribute(): array
    {
        return $this->path_names ? explode('/', $this->path_names) : [];
    }

    /**
     * Get full URL
     */
    public function getUrlAttribute(): string
    {
        return '/categories/' . ($this->path_slugs ?? $this->slug);
    }

    /**
     * Get depth level (0 = root)
     */
    public function getLevelAttribute(): int
    {
        return $this->path ? count(explode('/', $this->path)) - 1 : 0;
    }

    /**
     * Custom fields for compatibility
     */
    public function getCustomFieldsAttribute(): array
    {
        $hasCustomField = in_array(static::class, setting('custom_field_models', []), true);
        if (!$hasCustomField) {
            return [];
        }

        $array = $this->customFieldsValues()
            ->join('custom_fields', 'custom_fields.id', '=', 'custom_field_values.custom_field_id')
            ->where('custom_fields.in_table', true)
            ->get()
            ->toArray();

        return convertToAssoc($array, 'name');
    }

    /**
     * Check if has media
     */
    public function getHasMediaAttribute(): bool
    {
        return $this->hasMedia('image');
    }

    /**
     * Override media URL to handle fallbacks
     */
    public function getFirstMediaUrl(string $collectionName = 'default', string $conversion = ''): string
    {
        $url = $this->getFirstMediaUrlTrait($collectionName);
        $array = explode('.', $url);
        $extension = strtolower(end($array));

        if (in_array($extension, config('media-library.extensions_has_thumb'), true)) {
            return asset($this->getFirstMediaUrlTrait($collectionName, $conversion));
        }

        return asset(config('media-library.icons_folder') . '/' . $extension . '.png');
    }

    // ========================================
    // METHODS
    // ========================================

    /**
     * Check if this is a root category
     */
    public function isRoot(): bool
    {
        return is_null($this->parent_id);
    }

    /**
     * Check if has children
     */
    public function hasChildren(): bool
    {
        // Si la relation est déjà chargée
        if ($this->relationLoaded('children')) {
            return $this->children->isNotEmpty();
        }

        // Sinon, requête
        return $this->children()->exists();
    }

    /**
     * Get all descendant IDs
     */
    public function getDescendantIds(): array
    {
        return $this->descendants()->pluck('id')->toArray();
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Scope: Root categories only
     */
    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id')->orderBy('order');
    }

    /**
     * Scope: Featured categories
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('featured', true);
    }

    // ========================================
    // STATIC METHODS (TREE BUILDING)
    // ========================================

    /**
     * Retourne un arbre hiérarchique complet avec tous les descendants
     * Utilise une seule requête optimisée
     *
     * @param bool $withServices Inclure les services
     * @param bool $onlyFeatured Uniquement les catégories featured
     * @return array
     */
    public static function getTreeWithDescendants(bool $withServices = false, bool $onlyFeatured = false): array
    {
        // 1. Charger toutes les catégories en une seule requête
        $query = self::with('media');

        if ($withServices) {
            $query->with(['eServices.media', 'eServices.salon']);
        }

        if ($onlyFeatured) {
            $query->where('featured', true);
        }

        $allCategories = $query->orderBy('order')->get();

        // 2. Construire l'arbre en mémoire (TRÈS RAPIDE)
        return self::buildTree($allCategories, null, $withServices);
    }

    /**
     * Retourne uniquement les catégories racines avec leurs enfants directs
     *
     * @param bool $withServices Inclure les services
     * @return array
     */
    public static function getRootsWithChildren(bool $withServices = false): array
    {
        $query = self::roots()->with(['media', 'children.media']);

        if ($withServices) {
            $query->with(['eServices.media', 'eServices.salon', 'children.eServices']);
        }

        return $query->get()->map(function($category) use ($withServices) {
            return $category->toTreeNode($withServices, false);
        })->toArray();
    }

    /**
     * Construit récursivement l'arbre à partir d'une collection
     *
     * @param \Illuminate\Support\Collection $categories
     * @param int|null $parentId
     * @param bool $withServices
     * @return array
     */
    private static function buildTree($categories, ?int $parentId = null, bool $withServices = false): array
    {
        $branch = [];

        foreach ($categories as $category) {
            if ($category->parent_id === $parentId) {
                $node = $category->toTreeNode($withServices, true);

                // Récursion : chercher les enfants
                $children = self::buildTree($categories, $category->id, $withServices);
                $node['children'] = $children;

                $branch[] = $node;
            }
        }

        return $branch;
    }

    /**
     * Convertit la catégorie en nœud d'arbre
     *
     * @param bool $withServices
     * @param bool $checkChildren Vérifier si a des enfants
     * @return array
     */
    public function toTreeNode(bool $withServices = false, bool $checkChildren = true): array
    {
        $node = [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'color' => $this->color,
            'description' => $this->description,
            'featured' => $this->featured,
            'order' => $this->order,
            'image' => $this->getFirstMediaUrl('image'),
            'level' => $this->level,
            'url' => $this->url,
        ];

        if ($checkChildren) {
            $node['has_children'] = $this->hasChildren();
        }

        if ($withServices && $this->relationLoaded('eServices')) {
            $node['services_count'] = $this->eServices->count();
            $node['services'] = $this->eServices->map(function($service) {
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'price' => $service->price,
                    'discount_price' => $service->discount_price,
                    'image' => $service->getFirstMediaUrl('image'),
                ];
            })->toArray();
        }

        return $node;
    }
}