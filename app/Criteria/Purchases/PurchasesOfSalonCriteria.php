<?php
/*
 * File name: PurchasesOfSalonCriteria.php
 * Last modified: 2024.04.18 at 18:19:46
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Criteria\Bookings;

use Illuminate\Support\Facades\DB;
use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Class PurchasesOfSalonCriteria.
 *
 * @package namespace App\Criteria\Bookings;
 */
class PurchasesOfSalonCriteria implements CriteriaInterface
{
    /**
     * @var ?int
     */
    private ?int $salonId;

    /**
     * PurchasesOfSalonCriteria constructor.
     */
    public function __construct($salonId)
    {
        $this->salonId = $salonId;
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
        $salonId = DB::raw("json_extract(salon, '$.id')");
        return $model->where($salonId, $this->salonId)
            ->where('payment_status_id', '2')
            ->groupBy('purchases.id')
            ->select('purchases.*');

    }
}
