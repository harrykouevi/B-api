<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Image\Exceptions\InvalidManipulation;
use Spatie\Image\Manipulations;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ServiceTemplate extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia {
        getFirstMediaUrl as protected getFirstMediaUrlTrait;
    }

    protected $table = 'service_templates';
    protected $fillable = [
        'name',
        'description',
        'category_id'
    ];

    protected $casts = [
        'name' => 'string',
        'description' => 'string',
        'category_id' => 'integer'
    ];

    /**
     * Validation rules
     */
    public static array $rules = [
        'name' => 'required|max:127',
        'description' => 'required',
        'category_id' => 'required|exists:categories,id'
    ];


    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function optionTemplates(): HasMany
    {
        return $this->hasMany(OptionTemplate::class);
    }

    /**
     * Register media conversions for images
     * @param Media|null $media
     * @throws InvalidManipulation
     */
    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Manipulations::FIT_CROP, 200, 200)
            ->sharpen(10);

        $this->addMediaConversion('icon')
            ->fit(Manipulations::FIT_CROP, 100, 100)
            ->sharpen(10);
    }
}
