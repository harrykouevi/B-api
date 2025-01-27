<?php
/*
 * File name: SalonsCustomersCriteria.php
 * Last modified: 2024.04.18 at 17:21:43
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Criteria\Users;

use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Class SalonsCustomersCriteria.
 *
 * @package namespace App\Criteria\Users;
 */
class SalonsCustomersCriteria implements CriteriaInterface
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
        return $model->whereHas("roles", function ($q) {
            $q->where('name', '<>', 'admin');
        });
    }
}
