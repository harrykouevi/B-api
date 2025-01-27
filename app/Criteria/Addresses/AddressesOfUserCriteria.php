<?php
/*
 * File name: AddressesOfUserCriteria.php
 * Last modified: 2024.04.18 at 18:19:46
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Criteria\Addresses;

use App\Models\User;
use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Class AddressesOfUser.
 *
 * @package namespace App\Criteria\Bookings;
 */
class AddressesOfUserCriteria implements CriteriaInterface
{
    /**
     * @var ?int
     */
    private ?int $userId;

    /**
     * AddressesOfUser constructor.
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
            return $model;
        }
        return $model->where('addresses.user_id', $this->userId);
    }
}

