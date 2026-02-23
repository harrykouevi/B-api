<?php
/*
 * File name: PostsOfUserCriteria.php
 * Last modified: 2026.01.21 at 15:29:57
 * Author: harrykouevi - https://github.com/harrykouevi
 * Copyright (c) 2026
 */

namespace App\Criteria\Posts;

use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Class PostsOfUserCriteria.
 *
 * @package namespace App\Criteria\Posts;
 */
class PostsOfUserCriteria implements CriteriaInterface
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
        if (auth()->check() ){
            if (auth()->check() && auth()->user()->hasRole('admin')) {
                return $model;
            }
            if( auth()->user()->hasRole('salon owner')) {
                return $model->join('salon_users', 'salon_users.salon_id', '=', 'posts.salon_id')
                    ->groupBy('posts.id')
                    ->where('salon_users.user_id', auth()->id())
                    ->select('posts.*');
            } else {
                return $model->where('user_id', auth()->id())->select('posts.*')->groupBy('posts.id');
            }
        }

        return $model->whereRaw('1 = 0');
    }
}
