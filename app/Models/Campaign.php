<?php
/*
 * File name: Campaign.php
 * Last modified: 2025.03.09
 * Author: CHARM Platform
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Campaign extends Model
{
    public $table = 'campaigns';

    protected $fillable = [
        'title',
        'message',
        'audience',
        'sent_via',
        'topic',
        'sent_count',
        'status',
        'error_message',
        'sent_at',
        'created_by',
    ];

    protected $casts = [
        'sent_count' => 'integer',
        'sent_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
