<?php
/*
 * File name: PaidPurchasesCriteria.php
 * Last modified: 2025.08.29 at 13:21:42
 * Author: Harry.kouevi
 * Copyright (c) 2025
 */

namespace App\Criteria\Purchases;

use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Class PaidPurchasesCriteria.
 *
 * @package namespace App\Criteria\Purchases;
 */
class PaidPurchasesCriteria implements CriteriaInterface
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
        return $model->join('payments', 'payments.id', '=', 'purchases.payment_id')
            ->where('payments.payment_status_id', '2') // Paid Id
            ->groupBy('purchases.id')
            ->select('purchases.*');

    }
}
