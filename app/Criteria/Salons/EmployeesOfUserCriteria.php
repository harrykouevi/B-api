<?php
/*
 * File name: EmployeesOfUserCriteria.php
 * Last modified: 2025.03.11 at 05:20:33
 * Author: harrykouevi - https://github.com/harrykouevi
 * Copyright (c) 2025
 */

namespace App\Criteria\Salons;

use App\Models\User;
use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Class EmployeesOfUserCriteria.
 *
 * @package namespace App\Criteria\Salons;
 */
class EmployeesOfUserCriteria implements CriteriaInterface
{

    /**
     * @var ?int
     */
    private ?int $userId;

    /**
     * EmployeesOfUserCriteria constructor.
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
        if (auth()->user()->hasAnyRole(['salon owner'])) {
            return $model->join('salon_users', 'salon_users.user_id', '=', 'users.id')
                ->select('users.*')
                ->where('salon_users.user_id', $this->userId);
        } else {
            return $model;
        }
    }
}
