<?php
/*
 * File name: FavoryPostCriteria.php
 * Last modified: 2026.02.13 at 12:21:44
 * Author: harrykouevi - https://github.com/harrykouevi
 * Copyright (c) 2026
 * 
 */

namespace App\Criteria\Posts;

use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;
use Illuminate\Support\Facades\Auth;

/**
 * Class FavoryPostCriteria.
 *
 * @package namespace App\Criteria\Posts;
 */
class FavoryPostCriteria implements CriteriaInterface
{
    

    /**
     * Apply criteria in query repository
     *
     * @param $model
     * @param RepositoryInterface $repository
     *
     * @return mixed
     */
    public function apply($model, RepositoryInterface $repository): mixed
    {
        $userId = Auth::id();

        return $model
                    ->whereHas('favorites', function($query) use ($userId) {
                        $query->where('user_id', $userId);
                    })
                    // ->select('posts.*')
                    // ->join('favorite_posts', 'favorite_posts.post_id', '=', 'posts.id')
                    // ->where('favorite_posts.user_id', $userId)
                    // ->groupBy('posts.id')
                    ;
    }
}
