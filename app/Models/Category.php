<?php
/*
 * File name: Category.php
 * Last modified: 2024.04.18 at 17:53:30
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

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
 * @property string color
 * @property string description
 * @property integer order
 * @property boolean featured
 * @property boolean is_parent
 * @property integer parent_id
 */
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
    public static array $rules = ['name' => 'required|max:127', 'color' => 'required|max:36', 'description' => 'nullable', 'order' => 'nullable|numeric|min:0', 'parent_id' => 'nullable|exists:categories,id'];
    // public array $translatable = ['name', 'description'];
    public $table = 'categories';
    public $fillable = ['name', 'color', 'description', 'featured', 'order', 'parent_id'];
    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = ['name' => 'string', 'color' => 'string', 'description' => 'string', 'image' => 'string', 'featured' => 'boolean', 'order' => 'integer', 'parent_id' => 'integer'];
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
        if (in_array($extension, config('media-library.extensions_has_thumb'))) {
            return asset($this->getFirstMediaUrlTrait($collectionName, $conversion));
        } else {
            return asset(config('media-library.icons_folder') . '/' . $extension . '.png');
        }
    }

    public function getCustomFieldsAttribute(): array
    {
        $hasCustomField = in_array(static::class, setting('custom_field_models', []));
        if (!$hasCustomField) {
            return [];
        }
        $array = $this->customFieldsValues()->join('custom_fields', 'custom_fields.id', '=', 'custom_field_values.custom_field_id')->where('custom_fields.in_table', '=', true)->get()->toArray();

        return convertToAssoc($array, 'name');
    }

    public function customFieldsValues(): MorphMany
    {
        return $this->morphMany('App\Models\CustomFieldValue', 'customizable');
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
     * @return BelongsTo
     **/
    public function parentCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id', 'id');
    }

    /**
     * @return HasMany
     **/
    public function subCategories(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('order');
    }

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
        return $this->morphMany('App\Models\Discountable', 'discountable');
    }
}
