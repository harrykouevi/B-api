<?php
/*
 * File name: PostView.php
 * Last modified: 2026.02.13 at 13:22:27
 * Author: Harry.Kouevi
 * Copyright (c) 2026
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class PostView
 * @package App\Models
 *
 * @property EService eService
 * @property Collection option
 * @property User user
 * @property int post_id
 * @property int user_id
 */
class PostView extends Model
{
    use HasFactory;
    public $table = 'post_views';



    public $fillable = [
        'post_id',
        'user_id'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'post_id' => 'integer'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static array $rules = [
        'post_id' => 'required|exists:posts,id',
        'user_id' => 'required|exists:users,id'
    ];




    /**
     * @return BelongsTo
     **/
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id', 'id');
    }

    /**
     * @return BelongsTo
     **/
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

}
