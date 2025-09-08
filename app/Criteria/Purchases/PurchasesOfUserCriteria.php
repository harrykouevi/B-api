<?php
/*
 * File name: PurchasesOfUserCriteria.php
 * Last modified: 2025.08.29 at 13:21:47
 * Author: Harry.kouevi
 * Copyright (c) 2024
 */

namespace App\Criteria\Purchases;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Class PurchasesOfUserCriteria.
 *
 * @package namespace App\Criteria\Purchases;
 */
class PurchasesOfUserCriteria implements CriteriaInterface
{
    /**
     * @var ?int
     */
    private ?int $userId;

    /**
     * PurchasesOfUserCriteria constructor.
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
                ->groupBy('purchases.id')
                ->select('purchases.*');

        } else if (auth()->user()->hasRole('customer')) {
            return $model->where('purchases.user_id', $this->userId)
                ->select('purchases.*')
                ->groupBy('purchases.id');
        } else {
            return $model;
        }
    }
}
