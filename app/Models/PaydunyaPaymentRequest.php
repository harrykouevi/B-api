<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaydunyaPaymentRequest extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'user_id',
        'wallet_id',
        'amount',
        'reference_number',
        'status',
        'payment_channel',
        'description',
        'payment_url',
        'payload',
        'callback_payload',
        'completed_at',
    ];

    protected $casts = [
        'amount' => 'double',
        'payload' => 'array',
        'callback_payload' => 'array',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }
}
