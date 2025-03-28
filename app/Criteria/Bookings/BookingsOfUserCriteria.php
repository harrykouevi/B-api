<?php
/*
 * File name: BookingsOfUserCriteria.php
 * Last modified: 2024.04.18 at 18:21:47
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Criteria\Bookings;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Class BookingsOfUserCriteria.
 *
 * @package namespace App\Criteria\Bookings;
 */
class BookingsOfUserCriteria implements CriteriaInterface
{
    /**
     * @var ?int
     */
    private ?int $userId;

    /**
     * BookingsOfUserCriteria constructor.
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
        } else if (auth()->user()->hasRole('salon owner')) {
            $salonId = DB::raw("json_extract(salon, '$.id')");
            return $model->join("salon_users", "salon_users.salon_id", "=", $salonId)
                ->where('salon_users.user_id', $this->userId)
                ->groupBy('bookings.id')
                ->select('bookings.*');

        } else if (auth()->user()->hasRole('customer')) {
            return $model->where('bookings.user_id', $this->userId)
                ->select('bookings.*')
                ->groupBy('bookings.id');
        } else {
            return $model;
        }
    }
}
