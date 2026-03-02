<?php
/*
 * File name: FavoriteRepository.php
 * Last modified: 2024.04.18 at 17:21:53
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Repositories;


use App\Models\Following;
use InfyOm\Generator\Common\BaseRepository;

/**
 * Class FavoriteRepository
 * @package App\Repositories
 * @version January 22, 2021, 8:58 pm UTC
 *
 * @method Favorite findWithoutFail($id, $columns = ['*'])
 * @method Favorite find($id, $columns = ['*'])
 * @method Favorite first($columns = ['*'])
 */
namespace App\Repositories;

use App\Models\Following;
use InfyOm\Generator\Common\BaseRepository;

class FollowRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'follower_id',
        'user_id'
    ];

    public function model(): string
    {
        return Following::class;
    }

    public function VerifyexistingFollower($followerId, $followedId)
    {
        // On vérifie simplement si la ligne existe
        // Note: j'ai supprimé la ligne $user=... qui ne servait à rien ici
        return $this->model
            ->where('follower_id', $followerId)
            ->where('user_id', $followedId)
            ->exists();
    }

    public function getFollowingList($followerId)
    {
        // ATTENTION : with() prend le nom de la RELATION, pas le nom de la colonne
        // Dans ton modèle Following, la relation doit s'appeler 'user'
        return $this->model
            ->where('follower_id', $followerId)
            ->with('user') 
            ->get();
    }
}