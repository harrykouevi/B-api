<?php
/*
 * File name: WalletTransaction.php
 * Last modified: 2024.04.11 at 14:51:04
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Models;

use App\Events\WalletTransactionCreatedEvent;
use App\Events\WalletTransactionCreatingEvent;
use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Class WalletTransaction
 * @package App\Models
 * @version August 8, 2021, 3:57 pm CEST
 *
 * @property Wallet wallet
 * @property User user
 * @property double amount
 * @property string description
 * @property string action
 * @property string wallet_id
 * @property integer user_id
 */
class WalletTransaction extends Model
{
    use Uuids;
    use HasFactory;

    /**
     * Validation rules
     *
     * @var array
     */
    public static array $rules = [
        'amount' => 'required|numeric|min:0.01|max:99999999,99',
        'description' => 'nullable|max:255',
        'action' => ["required", "regex:/^(credit|debit)$/i"],
        'wallet_id' => 'required|exists:wallets,id',
        'payment_id' => 'required|exists:payments,id',
    ];
    public $table = 'wallet_transactions';
    public $fillable = [
        'amount',
        'description',
        'action',
        'wallet_id',
        'payment_id',
        'user_id'
    ];
    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'amount' => 'double',
        'description' => 'string',
        'action' => 'string'
    ];
    /**
     * New Attributes
     *
     * @var array
     */
    protected $appends = [
        'custom_fields',

    ];
    /**
     * The event map for the model.
     *
     * @var array
     */
    protected $dispatchesEvents = [
        'creating' => WalletTransactionCreatingEvent::class,
        'created' => WalletTransactionCreatedEvent::class,
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
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'wallet_id', 'id');
    }

    /**
     * @return BelongsTo
     **/
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id', 'id');
    }

    /**
     * @return BelongsTo
     **/
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
