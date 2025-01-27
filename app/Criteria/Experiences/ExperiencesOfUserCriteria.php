<?php
/*
 * File name: ExperiencesOfUserCriteria.php
 * Last modified: 2024.04.18 at 18:19:46
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Criteria\Experiences;

use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Class ExperiencesOfUserCriteria.
 *
 * @package namespace App\Criteria\Experiences;
 */
class ExperiencesOfUserCriteria implements CriteriaInterface
{
    /**
     * @var ?int
     */
    private ?int $userId;

    /**
     * ExperiencesOfUserCriteria constructor.
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
            return $model->join('salon_users', 'salon_users.salon_id', '=', 'experiences.salon_id')
                ->groupBy('experiences.id')
                ->select('experiences.*')
                ->where('salon_users.user_id', $this->userId);
        } else {
            return $model;
        }
    }
}
