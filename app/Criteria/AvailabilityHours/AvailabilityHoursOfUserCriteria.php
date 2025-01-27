<?php
/*
 * File name: AvailabilityHoursOfUserCriteria.php
 * Last modified: 2024.04.18 at 18:19:46
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Criteria\AvailabilityHours;

use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Class AvailabilityHoursOfUserCriteria.
 *
 * @package namespace App\Criteria\AvailabilityHours;
 */
class AvailabilityHoursOfUserCriteria implements CriteriaInterface
{
    /**
     * @var ?int
     */
    private ?int $userId;

    /**
     * AvailabilityHoursOfUserCriteria constructor.
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
            return $model->join('salon_users', 'salon_users.salon_id', '=', 'availability_hours.salon_id')
                ->groupBy('availability_hours.id')
                ->select('availability_hours.*')
                ->where('salon_users.user_id', $this->userId);
        } else {
            return $model;
        }
    }
}
