<?php
/*
 * File name: EService.php
 * Last modified: 2024.04.18 at 17:41:01
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Models;

use App\Casts\EServiceCast;
use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;
use Spatie\Image\Exceptions\InvalidManipulation;
use Spatie\Image\Manipulations;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Class EService
 * @package App\Models
 * @version January 19, 2021, 1:59 pm UTC
 *
 * @property Collection category
 * @property Salon salon
 * @property Collection Option
 * @property string name
 * @property integer id
 * @property double price
 * @property double discount_price
 * @property string duration
 * @property string description
 * @property boolean featured
 * @property boolean enable_booking
 * @property boolean enable_at_salon
 * @property boolean enable_at_customer_address
 * @property boolean available
 * @property integer salon_id
 */
class EService extends Model implements HasMedia, Castable
{
    use InteractsWithMedia {
        getFirstMediaUrl as protected getFirstMediaUrlTrait;
    }

    // use HasTranslations;
    use HasFactory;

    /**
     * Validation rules
     *
     * @var array
     */
    public static array $rules = [
        'name' => 'required|max:127',
        'price' => 'required|numeric|min:0|max:99999999,99',
        'discount_price' => 'nullable|numeric|min:0|max:99999999,99',
        'duration' => 'nullable|max:16',
        'description' => 'required',
        'salon_id' => 'required|exists:salons,id'
    ];
    public array $translatable = [
        'name',
        'description',
    ];
    public $table = 'e_services';
    public $fillable = [
        'name',
        'price',
        'discount_price',
        'duration',
        'description',
        'featured',
        'enable_booking',
        'enable_at_salon',
        'enable_at_customer_address',
        'available',
        'salon_id'
    ];
    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'name' => 'string',
        'image' => 'string',
        'price' => 'double',
        'discount_price' => 'double',
        'duration' => 'string',
        'description' => 'string',
        'featured' => 'boolean',
        'enable_booking' => 'boolean',
        'enable_at_salon' => 'boolean',
        'enable_at_customer_address' => 'boolean',
        'available' => 'boolean',
        'salon_id' => 'integer',
    ];
    /**
     * New Attributes
     *
     * @var array
     */
    protected $appends = [
        'custom_fields',
        'has_media',
        'is_favorite',
    ];

    protected $hidden = [
        "created_at",
        "updated_at",
    ];

    /**
     * @param array $arguments
     * @return string
     */
    public static function castUsing(array $arguments): string
    {
        return EServiceCast::class;
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

    /**
     * to generate media url in case of fallback will
     * return the file type icon
     * @param string $collectionName
     * @param string $conversion
     * @return string url
     */
    public function getFirstMediaUrl($collectionName = 'default', string $conversion = ''): string
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

    /**
     * Add Media to api results
     * @return bool
     */
    public function getHasMediaAttribute(): bool
    {
        return $this->hasMedia('image');
    }

    public function scopeNear($query, $latitude, $longitude)
    {
        // Calculate the distant in mile
        $distance = "SQRT(
                    POW(69.1 * (addresses.latitude - $latitude), 2) +
                    POW(69.1 * ($longitude - addresses.longitude) * COS(addresses.latitude / 57.3), 2))";

        // convert the distance to KM if the distance unit is KM
        if (setting('distance_unit') == 'km') {
            $distance .= " * 1.60934"; // 1 Mile = 1.60934 KM
        }

        return $query
            ->join('salons', 'salons.id', '=', 'e_services.salon_id')
            ->join('addresses', 'salons.address_id', '=', 'addresses.id')
            ->whereRaw("$distance < salons.availability_range")
            ->select(DB::raw($distance . " AS distance"), "e_services.*")
            ->orderBy('distance');
    }

    /**
     * Check if is a favorite for current user
     * @return bool
     */
    public function getIsFavoriteAttribute(): bool
    {
        return $this->favorites()->count() > 0;
    }

    /**
     * @return HasMany
     **/
    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class, 'e_service_id')->where('favorites.user_id', auth()->id());
    }

    /**
     * @return BelongsTo
     **/
    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class, 'salon_id', 'id');
    }

    /**
     * @return HasMany
     **/
    public function options(): HasMany
    {
        return $this->hasMany(Option::class, 'e_service_id');
    }

    /**
     * @return BelongsToMany
     **/
    public function optionGroups(): BelongsToMany
    {
        return $this->belongsToMany(OptionGroup::class, 'options')->distinct();
    }

    /**
     * @return BelongsToMany
     **/
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'e_service_categories');
    }

    /**
     * @return float
     */
    public function getPrice(): float
    {
        return $this->discount_price > 0 ? $this->discount_price : $this->price;
    }

    /**
     * @return bool
     */
    public function hasDiscount(): bool
    {
        return $this->discount_price > 0;
    }

    public function discountables(): MorphMany
    {
        return $this->morphMany('App\Models\Discountable', 'discountable');
    }
}
