<?php
/*
 * File name: PurchasesByBookingCriteria.php
 * Last modified: 2025.08.29 at 13:33:42
 * Author: Harry.kouevi
 * Copyright (c) 2025
 */

namespace App\Criteria\Purchases;

use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Class PurchasesByBookingCriteria.
 *
 * @package namespace App\Criteria\Purchases;
 */
class PurchasesByBookingCriteria implements CriteriaInterface
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
        return $model->where('purchases.booking', '!=', '')
                ->select('purchases.*')
                ->where('payments.payment_status_id', 2)
            ->groupBy('purchases.id')
            ->select('purchases.*');

    }
}
