<?php
/*
 * File name: SalonPayout.php
 * Last modified: 2024.04.18 at 17:50:01
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Date;

/**
 * Class SalonPayout
 * @package App\Models
 * @version January 30, 2021, 11:17 am UTC
 *
 * @property Salon salon
 * @property integer salon_id
 * @property string method
 * @property double amount
 * @property Date paid_date
 * @property string note
 */
class SalonPayout extends Model
{

    /**
     * Validation rules
     *
     * @var array
     */
    public static array $rules = [
        'salon_id' => 'required|exists:salons,id',
        'method' => 'required',
        'amount' => 'required|numeric|min:0.01|max:99999999,99'
    ];
    public $table = 'salon_payouts';
    public $fillable = [
        'salon_id',
        'method',
        'amount',
        'paid_date',
        'note'
    ];
    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'salon_id' => 'integer',
        'method' => 'string',
        'amount' => 'double',
        'paid_date' => 'datetime',
        'note' => 'string'
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
