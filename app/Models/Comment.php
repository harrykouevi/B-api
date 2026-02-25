<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model
{
    // On autorise le remplissage de ces colonnes
    protected $fillable = [
        'content',
        'user_id',
        'post_id'
    ];

    /**
     * New Attributes
     *
     * @var array
     */
    protected $appends = [
        
        'report_count'

    ];

    public function getReportCountAttribute()
    {
        return $this->reports()->count();
    }

    /**
     * Relation : Un commentaire appartient à un utilisateur (l'auteur)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relation : Un commentaire appartient à un post
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

     /**
     * A post can have many reports (polymorphic)
     */
    public function reports()
    {
        return $this->morphMany(Report::class, 'model');
    }

}