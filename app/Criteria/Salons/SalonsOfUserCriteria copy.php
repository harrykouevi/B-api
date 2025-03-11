<?php
/*
 * File name: SalonsOfUserCriteria.php
 * Last modified: 2024.04.18 at 18:20:33
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Criteria\Salons;

use App\Models\User;
use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Class SalonsOfUserCriteria.
 *
 * @package namespace App\Criteria\Salons;
 */
class SalonsOfUserCriteria implements CriteriaInterface
{

    /**
     * @var ?int
     */
    private ?int $userId;

    /**
     * SalonsOfUserCriteria constructor.
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
        } elseif (auth()->user()->hasAnyRole(['salon owner', 'customer'])) {
            return $model->join('salon_users', 'salon_users.salon_id', '=', 'salons.id')
                ->select('salons.*')
                ->where('salon_users.user_id', $this->userId);
        } else {
            return $model;
        }
    }
}
