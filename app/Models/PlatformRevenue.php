<?php
/*
 * File name: PlatformRevenue.php
 * Last modified: 2025.12.18
 * Author: CHARM Platform
 * Copyright (c) 2025
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class PlatformRevenue
 * @package App\Models
 *
 * Track all platform revenues (commissions, penalties, fees)
 *
 * @property int $id
 * @property string $type
 * @property float $amount
 * @property int|null $booking_id
 * @property int|null $salon_id
 * @property int|null $customer_id
 * @property string $description
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class PlatformRevenue extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'amount',
        'booking_id',
        'salon_id',
        'customer_id',
        'description'
    ];

    protected $casts = [
        'amount' => 'double',
    ];

    /**
     * Relation avec Booking
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Relation avec Salon
     */
    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class);
    }

    /**
     * Relation avec Customer (User)
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * Scope pour filtrer par type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope pour filtrer par période
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope pour les revenus positifs (gains)
     */
    public function scopeGains($query)
    {
        return $query->where('amount', '>', 0);
    }

    /**
     * Scope pour les revenus négatifs (remboursements)
     */
    public function scopeRefunds($query)
    {
        return $query->where('amount', '<', 0);
    }
}
