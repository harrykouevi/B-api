<?php
/*
 * File name: Earning.php
 * Last modified: 2024.04.18 at 17:50:01
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Class Earning
 * @package App\Models
 * @version January 30, 2021, 1:53 pm UTC
 *
 * @property Salon salon
 * @property integer salon_id
 * @property integer total_bookings
 * @property double total_earning
 * @property double admin_earning
 * @property double salon_earning
 * @property double taxes
 */
class Earning extends Model
{

    /**
     * Validation rules
     *
     * @var array
     */
    public static array $rules = [
        'salon_id' => 'required|exists:salons,id'
    ];
    public $table = 'earnings';
    public $fillable = [
        'salon_id',
        'total_bookings',
        'total_earning',
        'admin_earning',
        'salon_earning',
        'taxes'
    ];
    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'salon_id' => 'integer',
        'total_bookings' => 'integer',
        'total_earning' => 'double',
        'admin_earning' => 'double',
        'salon_earning' => 'double',
        'taxes' => 'double'
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
    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class, 'salon_id', 'id');
    }

}
