<?php
/*
 * File name: PaidBookingsCriteria.php
 * Last modified: 2024.04.18 at 17:21:42
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Criteria\Bookings;

use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Class PaidBookingsCriteria.
 *
 * @package namespace App\Criteria\Bookings;
 */
class PaidBookingsCriteria implements CriteriaInterface
{
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
        return $model->join('payments', 'payments.id', '=', 'bookings.payment_id')
            ->where('payments.payment_status_id', '2') // Paid Id
            ->groupBy('bookings.id')
            ->select('bookings.*');

    }
}
