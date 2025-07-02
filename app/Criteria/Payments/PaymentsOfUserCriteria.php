<?php
/*
 * File name: PaymentsOfUserCriteria.php
 * Last modified: 2024.04.18 at 18:19:46
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Criteria\Payments;

use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Class PaymentsOfUserCriteria.
 *
 * @package namespace App\Criteria\Payments;
 */
class PaymentsOfUserCriteria implements CriteriaInterface
{
    /**
     * @var ?int
     */
    private ?int $userId;

    /**
     * PaymentsOfUserCriteria constructor.
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
        if (auth()->check() && auth()->user()->hasRole('admin')) {
            return $model->select('payments.*');
        } else if (auth()->check() && auth()->user()->hasRole('salon owner')) {
            return $model->where('user_id', $this->userId)
                // ->groupBy('payments.id')
                ->select('payments.*');
        } else if (auth()->check() && auth()->user()->hasRole('customer')) {
            return $model->where('user_id', $this->userId)
                ->select('payments.*');
        } else {
            return $model->select('payments.*');
        }
    }
}