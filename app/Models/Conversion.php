<?php

namespace App\Models;

use App\Casts\AffiliateCast;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversion extends Model
{
    use HasFactory;
    /**
     * Validation rules
     *
     * @var array
     */
    public static array $rules = [
        'status' => 'max:255',
    ];
    public $table = 'conversions';
    public $fillable = [
        'status',
        'affiliate_id',
        'affiliate'
    ];
    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'status' => 'string',
        'affiliate_id' => 'integer',
        'affiliate' => AffiliateCast::class,
    ];

    protected $hidden = [
        "created_at",
        "updated_at",
    ];


    /**
     * @return BelongsTo
     **/
    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class, 'affiliate_id', 'id');
    }
}
