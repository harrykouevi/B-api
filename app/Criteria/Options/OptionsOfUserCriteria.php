<?php
/*
 * File name: OptionsOfUserCriteria.php
 * Last modified: 2024.04.18 at 18:21:47
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Criteria\Options;

use App\Models\User;
use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Class OptionsOfUserCriteria.
 *
 * @package namespace App\Criteria\Options;
 */
class OptionsOfUserCriteria implements CriteriaInterface
{

    /**
     * @var ?int
     */
    private ?int $userId;

    /**
     * OptionsOfUserCriteria constructor.
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
            return $model->join('e_services', 'options.e_service_id', '=', 'e_services.id')
                ->join('salon_users', 'salon_users.salon_id', '=', 'e_services.salon_id')
                ->groupBy('options.id')
                ->select('options.*')
                ->where('salon_users.user_id', $this->userId);
        } else {
            return $model;
        }
    }
}
