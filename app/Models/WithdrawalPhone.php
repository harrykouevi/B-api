<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WithdrawalPhone extends Model
{
    use HasFactory;

    public const ACCOUNT_TYPE_YAS = 'yas';
    public const ACCOUNT_TYPE_MOOV = 'moov';

    protected $fillable = [
        'user_id',
        'phone_number',
        'account_type',
        'is_sync_cinetpay',
    ];

    protected $casts = [
        'is_sync_cinetpay' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Vérifie si le numéro est synchronisé avec CinetPay
     */
    public function isSynced(): bool
    {
        return $this->is_sync_cinetpay === true;
    }

    /**
     * Marque le numéro comme synchronisé avec CinetPay
     */
    public function markAsSynced(): void
    {
        $this->update(['is_sync_cinetpay' => true]);
    }

    /**
     * Marque le numéro comme non synchronisé avec CinetPay
     */
    public function markAsNotSynced(): void
    {
        $this->update(['is_sync_cinetpay' => false]);
    }
}