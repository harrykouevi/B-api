<?php
/*
 * File name: EarningOfUserCriteria.php
 * Last modified: 2024.04.18 at 17:30:50
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Criteria\Earnings;

use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Class EarningOfUserCriteria.
 *
 * @package namespace App\Criteria\Earnings;
 */
class EarningOfUserCriteria implements CriteriaInterface
{
    private $userId;

    /**
     * EarningOfUserCriteria constructor.
     * @param $userId
     */
    public function __construct($userId)
    {
        $this->userId = $userId;
    }


    /**
     * Apply criteria in query repository
     *
     * @param string              $model
     * @param RepositoryInterface $repository
     *
     * @return mixed
     */
    public function apply($model, RepositoryInterface $repository): mixed
    {
        if (auth()->user()->hasRole('admin')) {
            return $model;
        } else if ((auth()->user()->hasRole('salon owner'))) {
            return $model->join("salon_users", "salon_users.salon_id", "=", "earnings.salon_id")
                ->groupBy('earnings.id')
                ->where('salon_users.user_id', $this->userId);
        } else {
            return $model;
        }
    }
}
