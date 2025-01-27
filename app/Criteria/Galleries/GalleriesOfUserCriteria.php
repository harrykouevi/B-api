<?php
/*
 * File name: GalleriesOfUserCriteria.php
 * Last modified: 2024.04.18 at 18:21:47
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Criteria\Galleries;

use App\Models\User;
use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Class GalleriesOfUserCriteria.
 *
 * @package namespace App\Criteria\Galleries;
 */
class GalleriesOfUserCriteria implements CriteriaInterface
{
    /**
     * @var ?int
     */
    private ?int $userId;

    /**
     * GalleriesOfUserCriteria constructor.
     */
    public function __construct($userId)
    {
        $this->userId = $userId;
    }

    /**
     * Apply criteria in query repository
     *
     * @param string $model
     * @param RepositoryInterface $repository
     *
     * @return mixed
     */
    public function apply($model, RepositoryInterface $repository): mixed
    {
        if (auth()->user()->hasRole('admin')) {
            return $model;
        } elseif (auth()->user()->hasRole('salon owner')) {
            return $model->join('salon_users', 'salon_users.salon_id', '=', 'galleries.salon_id')
                ->groupBy('galleries.id')
                ->select('galleries.*')
                ->where('salon_users.user_id', $this->userId);
        } else {
            return $model;
        }
    }
}
