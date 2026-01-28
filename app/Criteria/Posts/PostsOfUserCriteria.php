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
     * @var ?int
     */
    private ?int $userId;

    /**
     * PostsOfUserCriteria constructor.
     */
    public function __construct($userId)
    {
        $this->userId = $userId;
    }

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
        if (auth()->check() && auth()->user()->hasRole('salon owner')) {
            return $model->join('salon_users', 'salon_users.salon_id', '=', 'posts.salon_id')
                ->groupBy('posts.id')
                ->where('salon_users.user_id', $this->userId)
                ->select('posts.*');
        } else {
            return $model->select('posts.*')->groupBy('posts.id');
        }
    }
}
