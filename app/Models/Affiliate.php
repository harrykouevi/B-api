<?php
/*
 * File name: Affiliate.php
 * Last modified: 2025.02.10 at 11:53:44
 * Author: harrykouevi - https://github.com/harrykouevi
 * Copyright (c) 2025
 */

namespace App\Models;

use App\Casts\AffiliateCast;
use Eloquent as Model;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Class Affiliate
 * @package App\Models
 * @version Fevrier 10, 2025, 8:02 pm UTC
 *
 * @property User user
 * @property integer id
 * @property string link
 * @property integer user_id
 */
class Affiliate extends Model 
{

    use HasFactory;
    /**
     * Validation rules
     *
     * @var array
     */
    public static array $rules = [
        'link' => 'max:255',
    ];
    public $table = 'affiliates';
    public $fillable = [
        'link',
        'user_id'
    ];
    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'link' => 'string',
        'user_id' => 'integer'
    ];

    protected $hidden = [
        "created_at",
        "updated_at",
    ];


    /**
     * @return BelongsTo
     **/
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
