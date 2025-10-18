<?php

namespace App\Models;

use App\Casts\ServiceTempplateCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\Image\Manipulations;
use Spatie\MediaLibrary\MediaCollections\Models\Media;



/**
 * ServiceTemplate
 * @package App\Models
 * @version August 8, 2021, 3:57 pm CEST
 *
 * @property string description
 * @property string name
 */
class ServiceTemplate extends Model implements HasMedia
{
    use InteractsWithMedia {
        getFirstMediaUrl as protected getFirstMediaUrlTrait;
    }

    use HasFactory;

    public $table = 'service_templates';
    protected $fillable = [
        'name',
        'description',
        'category_id'
    ];

    /**
     * New Attributes
     *
     * @var array
     */
    protected $appends = [
        'custom_fields',
        'has_media',

    ];

 
    /**
     * Validation rules
     */
    public static array $rules = [
        'name' => 'required|max:127',
        'description' => 'required',
        'category_id' => 'required|exists:categories,id'
    ];


    /**
     * Add Media to api results
     * @return bool
     */
    public function getHasMediaAttribute(): bool
    {
        return $this->hasMedia('image');
    }

    
    /**
     * @param Media|null $media
     * @throws InvalidManipulation
     */
    public function registerMediaConversions(Media $media = null) :void
    {
        $this->addMediaConversion('thumb')
            ->fit(Manipulations::FIT_CROP, 200, 200)
            ->sharpen(10);

        $this->addMediaConversion('icon')
            ->fit(Manipulations::FIT_CROP, 100, 100)
            ->sharpen(10);
    }


    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function getCustomFieldsAttribute(): array
    {
        $hasCustomField = in_array(static::class, setting('custom_field_models', []));
        if (!$hasCustomField) {
            return [];
        }
        $array = $this->customFieldsValues()
            ->join('custom_fields', 'custom_fields.id', '=', 'custom_field_values.custom_field_id')
            ->where('custom_fields.in_table', '=', true)
            ->get()->toArray();

        return convertToAssoc($array, 'name');
    }

    public function customFieldsValues(): MorphMany
    {
        return $this->morphMany('App\Models\CustomFieldValue', 'customizable');
    }

}
