<?php
/*
 * File name: AwardsOfUserCriteria.php
 * Last modified: 2024.04.18 at 18:19:46
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Criteria\Awards;

use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Class AwardsOfUserCriteria.
 *
 * @package namespace App\Criteria\Awards;
 */
class AwardsOfUserCriteria implements CriteriaInterface
{
    /**
     * @var ?int
     */
    private ?int $userId;

    /**
     * AwardsOfUserCriteria constructor.
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
        if (auth()->check() && auth()->user()->hasRole('salon owner')) {
            return $model->join('salon_users', 'salon_users.salon_id', '=', 'awards.salon_id')
                ->groupBy('awards.id')
                ->select('awards.*')
                ->where('salon_users.user_id', $this->userId);
        } else {
            return $model;
        }
    }
}
