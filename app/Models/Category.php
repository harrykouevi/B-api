<?php
/*
 * File name: Category.php
 * Last modified: 2024.04.18 at 17:53:30
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Models;

use App\Observers\CategoryObserver;
use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use App\Models\CustomFieldValue;
use App\Models\Discountable;

/**
 * Class Category
 * @package App\Models
 * @version January 19, 2021, 2:04 pm UTC
 *
 * @property Category parentCategory
 * @property Category[] subCategories
 * @property EService[] featuredEServices
 * @property EService[] eServices
 * @property string name
 * @property string slug
 * @property string color
 * @property string description
 * @property integer order
 * @property boolean featured
 * @property boolean is_parent
 * @property integer parent_id
 * @property string path
 * @property string path_slugs
 * @property string path_names
 */
#[ObservedBy([CategoryObserver::class])]
class Category extends Model implements HasMedia
{
    use InteractsWithMedia {
        getFirstMediaUrl as protected getFirstMediaUrlTrait;
    }

    // // use HasTranslations;

    /**
     * Validation rules
     *
     * @var array
     */
    public static array $rules = [
        'name' => 'required|max:127',
        'color' => 'required|max:36',
        'description' => 'nullable',
        'order' => 'nullable|numeric|min:0',
        'parent_id' => 'nullable|exists:categories,id'
    ];
    // public array $translatable = ['name', 'description'];
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
    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
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
    /**
     * New Attributes
     *
     * @var array
     */
    protected $appends = ['custom_fields', 'has_media'];

    protected $hidden = ["created_at", "updated_at",];

    /**
     * to generate media url in case of fallback will
     * return the file type icon
     * @param string $collectionName
     * @param string $conversion
     * @return string
     */
    public function getFirstMediaUrl(string $collectionName = 'default', string $conversion = ''): string
    {
        $url = $this->getFirstMediaUrlTrait($collectionName);
        $array = explode('.', $url);
        $extension = strtolower(end($array));
        if (in_array($extension, config('media-library.extensions_has_thumb'), true)) {
            return asset($this->getFirstMediaUrlTrait($collectionName, $conversion));
        } else {
            return asset(config('media-library.icons_folder') . '/' . $extension . '.png');
        }
    }

    public function getCustomFieldsAttribute(): array
    {
        $hasCustomField = in_array(static::class, setting('custom_field_models', []), true);
        if (!$hasCustomField) {
            return [];
        }
        $array = $this->customFieldsValues()->join('custom_fields', 'custom_fields.id', '=', 'custom_field_values.custom_field_id')->where('custom_fields.in_table', '=', true)->get()->toArray();

        return convertToAssoc($array, 'name');
    }

    public function customFieldsValues(): MorphMany
    {
        return $this->morphMany(CustomFieldValue::class, 'customizable');
    }

    /**
     * Add Media to api results
     * @return bool
     */
    public function getHasMediaAttribute(): bool
    {
        return $this->hasMedia('image');
    }

    /**
     * PROPRE À LA  CATÉGORIE
     */

    /**
     * @return BelongsTo
     **/
    public function parentCategory(): BelongsTo
    {
        return $this->belongsTo(__CLASS__, 'parent_id', 'id');
    }

    /**
     * @return HasMany
     **/
    public function subCategories(): HasMany
    {
        return $this->hasMany(__CLASS__, 'parent_id')->orderBy('order');
    }

    public function descendants()
    {
        return self::where('path', 'like', $this->path . '/%');
    }

    public function getBreadcrumbAttribute(): array
    {
        return $this->path_names ? explode('/', $this->path_names) : [];
    }

    public function getUrlAttribute(): string
    {
        return '/categories/' . $this->path_slugs;
    }

    public function getLevelAttribute(): int
    {
        return $this->path ? count(explode('/', $this->path)) - 1 : 0;
    }

    public function isRoot(): bool
    {
        return is_null($this->parent_id);
    }

    public function hasChildren(): bool
    {
        return $this->subCategories->exists();
    }

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id')->orderBy('order');
    }

    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }

    /*
     * FIN
     */

    /**
     * @return BelongsToMany
     **/
    public function eServices(): BelongsToMany
    {
        return $this->belongsToMany(EService::class, 'e_service_categories');
    }

    /**
     * @return BelongsToMany
     **/
    public function featuredEServices(): BelongsToMany
    {
        return $this->belongsToMany(EService::class, 'e_service_categories')->where('e_services.featured', '=', true);
    }

    public function discountables(): MorphMany
    {
        return $this->morphMany(Discountable::class, 'discountable');
    }
}
