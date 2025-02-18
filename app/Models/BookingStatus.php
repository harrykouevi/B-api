<?php
/*
 * File name: BookingStatus.php
 * Last modified: 2024.04.18 at 17:50:01
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Models;

use App\Traits\HasTranslations;
use Eloquent as Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Class BookingStatus
 * @package App\Models
 * @version January 25, 2021, 7:18 pm UTC
 *
 * @property string status
 * @property int order
 * @method hasMedia(mixed $collection)
 * @method getFirstMedia(mixed $collection)
 */
class BookingStatus extends Model
{

    // use HasTranslations;

    /**
     * Validation rules
     *
     * @var array
     */
    public static array $rules = [
        'status' => 'required|max:127',
        'order' => 'min:0'
    ];

    public array $translatable = [
        'status',
    ];
    public $table = 'booking_statuses';
    public $fillable = [
        'status',
        'order'
    ];
    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'status' => 'string'
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


}
