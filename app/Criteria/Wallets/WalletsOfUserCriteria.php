<?php
/*
 * File name: WalletsOfUserCriteria.php
 * Last modified: 2024.04.18 at 18:21:47
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Criteria\Wallets;

use App\Models\User;
use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Class WalletsOfUserCriteria.
 *
 * @package namespace App\Criteria\Options;
 */
class WalletsOfUserCriteria implements CriteriaInterface
{

    /**
     * @var ?int
     */
    private ?int $userId;

    /**
     * WalletsOfUserCriteria constructor.
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
        if (auth()->check() && !auth()->user()->hasRole('admin')) {
            return $model->where('wallets.user_id', $this->userId);
        } else {
            return $model;
        }
    }
}
