<?php
/*
 * File name: Salon.php
 * Last modified: 2024.04.18 at 17:41:01
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Models;

use App\Casts\SalonCast;
use App\Traits\HasTranslations;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use DateTime;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;
use Nwidart\Modules\Exceptions\ModuleNotFoundException;
use Nwidart\Modules\Facades\Module;
use Spatie\Image\Exceptions\InvalidManipulation;
use Spatie\Image\Manipulations;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\OpeningHours\OpeningHours;

/**
 * Class Salon
 * @package App\Models
 * @version January 13, 2021, 11:11 am UTC
 *
 * @property SalonLevel salonLevel
 * @property Collection[] users
 * @property Collection[] taxes
 * @property Address address
 * @property Collection[] awards
 * @property Collection[] experiences
 * @property Collection salonSubscriptions
 * @property Collection[] availabilityHours
 * @property Collection[] eServices
 * @property Collection[] galleries
 * @property integer id
 * @property string name
 * @property integer salon_level_id
 * @property string description
 * @property string phone_number
 * @property string mobile_number
 * @property double availability_range
 * @property boolean available
 * @property boolean featured
 * @property boolean accepted
 */
class Salon extends Model implements HasMedia, Castable
{
    use InteractsWithMedia {
        getFirstMediaUrl as protected getFirstMediaUrlTrait;
    }
    // // use HasTranslations;
    use HasFactory;

    /**
     * Validation rules
     *
     * @var array
     */
    public static array $rules = [
        'name' => 'required|max:127',
        // 'salon_level_id' => 'required|exists:salon_levels,id',
        'address_id' => 'required|exists:addresses,id',
        'phone_number' => 'required|max:50',
        'mobile_number' => 'required|max:50',
        'availability_range' => 'numeric|max:9999999.99|min:0.01',
        'available' => 'boolean',
        'featured' => 'boolean',
        'accepted' => 'required|boolean',
    ];
    // public array $translatable = [
    //     'name',
    //     'description',
    // ];
    public $table = 'salons';
    public $fillable = [
        'name',
        'salon_level_id',
        'address_id',
        'description',
        'phone_number',
        'mobile_number',
        'availability_range',
        'available',
        'featured',
        'accepted'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'image' => 'string',
        'name' => 'string',
        'salon_level_id' => 'integer',
        'address_id' => 'integer',
        'description' => 'string',
        'phone_number' => 'string',
        'mobile_number' => 'string',
        'availability_range' => 'double',
        'available' => 'boolean',
        'featured' => 'boolean',
        'accepted' => 'boolean'
    ];
    /**
     * New Attributes
     *
     * @var array
     */
    protected $appends = [
        'custom_fields',
        'has_media',
        'rate',
        'closed',
        'total_reviews',
        'has_valid_subscription',
        'address_name',
        'city',
        'district'
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
        return SalonCast::class;
    }

    public function discountables(): MorphMany
    {
        return $this->morphMany('App\Models\Discountable', 'discountable');
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
     * Retourne une fonction de filtrage permettant de déterminer si un salon est fermé ou ouvert,
     * en se basant dynamiquement sur ses horaires de disponibilité.
     *
     * Cette fonction est conçue pour être utilisée avec une collection Laravel :
     * ex: $salons->filter(Salon::scopedClosed(true))
     *
     * @param bool $closed  
     * @return \Closure     
     */
    public static function scopedClosed(bool $closed)
    {
        return function ($salon) use ($closed) {
            $openingHoursArray = [];
            foreach ($salon->availabilityHours as $element) {
                $openingHoursArray[$salon->toEnglishday($element['day'])][] =
                    $element['start_at'] . '-' . $element['end_at'];
            }
            $openingHours = \Spatie\OpeningHours\OpeningHours::createAndMergeOverlappingRanges($openingHoursArray);
            return $openingHours->isClosed() == $closed;
        };
    }


    public function scopeNear($query, $latitude, $longitude, $areaLatitude, $areaLongitude)
    {
        // Calculate the distant in mile
        $distance = "SQRT(
                    POW(69.1 * (addresses.latitude - $latitude), 2) +
                    POW(69.1 * ($longitude - addresses.longitude) * COS(addresses.latitude / 57.3), 2))";

        // Calculate the distant in mile
        $area = "SQRT(
                    POW(69.1 * (addresses.latitude - $areaLatitude), 2) +
                    POW(69.1 * ($areaLongitude - addresses.longitude) * COS(addresses.latitude / 57.3), 2))";

        // convert the distance to KM if the distance unit is KM
        if (setting('distance_unit') == 'km') {
            $distance .= " * 1.60934"; // 1 Mile = 1.60934 KM
            $area .= " * 1.60934"; // 1 Mile = 1.60934 KM
        }

        return $query
            ->join('addresses', 'salons.address_id', '=', 'addresses.id')
            // ->whereRaw("$distance < salons.availability_range")
            ->select(DB::raw($distance . " AS distance"), DB::raw($area . " AS area"), "salons.*")
            ->orderBy('area');
    }

    /**
     * Provider ready when he is accepted by admin and marked as available
     * and is open now
     */
    public function getClosedAttribute(): bool
    {
        $isAvailable = array_key_exists('available', $this->attributes) ? $this->attributes['available'] : false;

        return !$this->accepted || !$isAvailable || $this->openingHours()->isClosed();
    }

    public function openingHours(): OpeningHours
    {
        $openingHoursArray = [];
        foreach ($this->availabilityHours as $element) {
            $openingHoursArray[$this->toEnglishday($element['day'])][] = $element['start_at'] . '-' . $element['end_at'];
        }
        return OpeningHours::createAndMergeOverlappingRanges($openingHoursArray);
    }

    private function toEnglishday(String $day)
    {
        $dayarray = [
            "lundi"=>"monday" ,
            "mardi"=>"tuesday" ,
            "mercredi"=>"wednesday" ,
            "jeudi"=>"thursday" ,
            "vendredi"=>"friday" ,
            "samedi"=>"saturday" ,
            "dimanche"=>"sunday" ,
        ];
        if (array_key_exists(strtolower($day), $dayarray)) {  
            return $dayarray[strtolower($day)];
        }else{
            if(in_array(strtolower($day), $dayarray) ){ 
                return strtolower($day) ;
            }else {
                throw new Exception("Attempt $day is not a supported day.");
            }
        }
    }

    /**
     * get each range of 30 min with open/close salon
     */
    public function weekCalendarRange(Carbon $date, int $employeeId): array
    {
        $period = CarbonPeriod::since($date->subDay()->ceilDay())->minutes(30)->until($date->addDay()->ceilDay()->subMinutes(30));
        $dates = [];
        // Iterate over the period
        foreach ($period as $key => $d) {
            $firstDate = $d->locale('en')->toDateTime();
            $isOpen = $firstDate > new DateTime("now");
            if ($isOpen) {
                $isOpen = $this->openingHours()->isOpenAt($firstDate);
                if ($isOpen && $employeeId != 0) {
                    $isOpen = !($this->bookings()->where('booking_at', '=', $firstDate)
                        ->where('cancel', '<>', '1')
                        ->whereNotIn('booking_status_id', ['6', '7'])
                        ->where('employee_id', '=', $employeeId)
                        ->count());
                }
            }
            $times = $d->locale('en')->toIso8601String();
            $dates[] = [$times, $isOpen];
        }
        return $dates;
    }

    /**
     * @return HasMany
     **/
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'salon->id')->orderBy('booking_at');
    }

    public function getRateAttribute(): float
    {
        return (float)$this->salonReviews()->avg('rate');
    }

    /**
     * @return HasManyThrough
     **/
    public function salonReviews(): HasManyThrough
    {
        return $this->hasManyThrough(SalonReview::class, Booking::class, "salon->id", 'booking_id', 'id', 'id');
    }

    public function getTotalReviewsAttribute(): float
    {
        return $this->salonReviews()->count();
    }

    public function getAddressNameAttribute(): ?string
    {
        return $this->address?->address;
    }

    public function getCityAttribute(): ?string
    {
        return $this->address?->city;
    }

    public function getDistrictAttribute(): ?string
    {
        return $this->address?->district;
    }

    public function getHasValidSubscriptionAttribute(): ?bool
    {
        if (!Module::isActivated('Subscription')) {
            return null;
        }
        $result = $this->salonSubscriptions
            ->where('expires_at', '>', now())
            ->where('starts_at', '<=', now())
            ->where('active', '=', 1)
            ->count();
        return $result > 0;
    }

    /**
     * @return BelongsTo
     **/
    public function salonLevel(): BelongsTo
    {
        return $this->belongsTo(SalonLevel::class, 'salon_level_id', 'id');
    }

    /**
     * @return HasMany
     **/
    public function awards(): HasMany
    {
        return $this->hasMany(Award::class, 'salon_id');
    }

    /**
     * @return HasMany
     **/
    public function experiences(): HasMany
    {
        return $this->hasMany(Experience::class, 'salon_id');
    }

    /**
     * @return HasMany
     *
     * @throws ModuleNotFoundException
     */
    public function salonSubscriptions(): HasMany
    {
        if (Module::isActivated('Subscription'))
            return $this->hasMany('Modules\Subscription\Models\SalonSubscription', 'salon_id');
        else
            throw new ModuleNotFoundException();

    }

    /**
     * @return HasMany
     **/
    public function availabilityHours(): HasMany
    {
        return $this->hasMany(AvailabilityHour::class, 'salon_id')->orderBy('start_at');
    }

    /**
     * @return HasMany
     **/
    public function eServices(): HasMany
    {
        return $this->hasMany(EService::class, 'salon_id');
    }

    /**
     * @return HasMany
     **/
    public function galleries(): HasMany
    {
        return $this->hasMany(Gallery::class, 'salon_id');
    }

    /**
     * @return BelongsToMany
     **/
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'salon_users');
    }

    /**
     * @return BelongsTo
     **/
    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'address_id');
    }

    /**
     * @return BelongsToMany
     **/
    public function taxes(): BelongsToMany
    {
        return $this->belongsToMany(Tax::class, 'salon_taxes');
    }

    /**
     * Add Media to api results
     * @return bool
     */
    public function getHasMediaAttribute(): bool
    {
        return $this->hasMedia('image');
    }
}
