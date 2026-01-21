<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model; // On importe le vrai Model de Laravel
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestPost extends Model
/**
 * Class TestPost
 * @package App\Models
 */

{
    // Important : On précise que la table reste 'posts' (ou 'test_posts' selon ta base)
    public $table = 'e_services';

    public $fillable = [
        'description',
        'salon_id',
        'user_id'
    ];

    protected $casts = [
        'id' => 'integer',
        'description' => 'string',
        'salon_id' => 'integer',
        'user_id' => 'integer'
    ];

    public static $rules = [
        'description' => 'required',
        'salon_id' => 'required|exists:salons,id',
        'user_id' => 'required|exists:users,id'
    ];

    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class, 'salon_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}