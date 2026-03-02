<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Following extends Model
{
    public $table = 'followings';

    protected $fillable = ['id', 'user_id', 'follower_id'];

    // Un utilisateur est suivi par un autre utilisateur
    public function follower()
    {
        return $this->belongsTo(User::class , 'follower_id', 'id');
    }
// Dans App\Models\Following.php

    // Un like appartient à un utilisateur
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id' );
    }
}