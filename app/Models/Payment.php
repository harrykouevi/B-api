<?php
/*
 * File name: Payment.php
 * Last modified: 2024.04.18 at 17:50:01
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Class Payment
 * @package App\Models
 * @version January 7, 2021, 4:54 pm UTC
 *
 * @property User user
 * @property Booking booking
 * @property PaymentMethod paymentMethod
 * @property PaymentStatus paymentStatus
 * @property double amount
 * @property string description
 * @property integer user_id
 * @property integer payment_method_id
 * @property integer payment_status_id
 */
class Payment extends Model
{

    /**
     * Validation rules
     *
     * @var array
     */
    public static array $rules = [
        'amount' => 'required|min:0',
        'description' => 'required|max:255',
        'user_id' => 'required|exists:users,id',
        'payment_method_id' => 'required|exists:payment_methods,id',
        'payment_status_id' => 'required|exists:payment_statuses,id'
    ];
    public $table = 'payments';
    public $fillable = [
        'amount',
        'description',
        'user_id',
        'payment_method_id',
        'payment_status_id'
    ];
    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'amount' => 'double',
        'description' => 'string',
        'user_id' => 'integer',
        'payment_method_id' => 'integer',
        'payment_status_id' => 'integer'
    ];
    /**
     * New Attributes
     *
     * @var array
     */
    protected $appends = [
        'custom_fields',

    ];

    public function getExtendedIdAttribute(): string
    {
        return $this->id . " - " . $this->created_at->timestamp ;
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
     * @return BelongsTo
     **/
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * @return BelongsTo
     **/
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id', 'id');
    }

    /**
     * @return BelongsTo
     **/
    public function paymentStatus(): BelongsTo
    {
        return $this->belongsTo(PaymentStatus::class, 'payment_status_id', 'id');
    }

    /**
     * @return HasOne
     **/
    public function booking(): HasOne
    {
        return $this->hasOne(Booking::class, 'payment_id', 'id');
    }

    /**
     * @return HasMany
     **/
    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class, 'payment_id');
    }

}
