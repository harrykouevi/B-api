<?php
/*
 * File name: SalonReview.php
 * Last modified: 2024.04.18 at 17:53:44
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Class SalonReview
 * @package App\Models
 * @version January 23, 2021, 7:42 pm UTC
 *
 * @property User user
 * @property Salon salon
 * @property EService[] e_services
 * @property string review
 * @property double rate
 * @property integer user_id
 * @property integer employee_id
 * @property integer salon_id
 * @method hasMedia(mixed $collection)
 * @method getFirstMedia(mixed $collection)
 */
class SalonReview extends Model
{
    use HasFactory;
    /**
     * Validation rules
     *
     * @var array
     */
    public static array $rules = [
        'rate' => 'required|numeric|max:5|min:0',
        'booking_id' => 'required|exists:bookings,id'
    ];
    public $table = 'salon_reviews';
    public $fillable = [
        'review',
        'rate',
        'booking_id',
    ];
    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'review' => 'string',
        'rate' => 'double',
        'booking_id' => 'integer'
    ];
    /**
     * New Attributes
     *
     * @var array
     */
    protected $appends = [
        'custom_fields',

    ];

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
     * @return BelongsTo
     **/
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

}
