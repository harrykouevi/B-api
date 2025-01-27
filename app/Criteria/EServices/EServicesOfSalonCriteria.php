<?php
/*
 * File name: EServicesOfSalonCriteria.php
 * Last modified: 2024.04.18 at 18:19:46
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Criteria\EServices;


use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Class EServicesOfSalonCriteria.
 *
 * @package namespace App\Criteria\EServices;
 */
class EServicesOfSalonCriteria implements CriteriaInterface
{
    /**
     * @var ?int
     */
    private ?int $userId;

    /**
     * EServicesOfSalonCriteria constructor.
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
        if (auth()->check() && auth()->user()->hasAnyRole(['customer', 'salon owner'])) {
            return $model->join('salon_users', 'salon_users.salon_id', '=', 'e_services.salon_id')
                ->groupBy('e_services.id')
                ->where('salon_users.user_id', $this->userId)
                ->select('e_services.*');
        } else {
            return $model->select('e_services.*')->groupBy('e_services.id');
        }
    }
}
