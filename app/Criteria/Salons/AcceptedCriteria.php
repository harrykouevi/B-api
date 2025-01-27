<?php
/*
 * File name: AcceptedCriteria.php
 * Last modified: 2024.04.18 at 17:21:25
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Criteria\Salons;

use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Class AcceptedCriteria.
 *
 * @package namespace App\Criteria\Salons;
 */
class AcceptedCriteria implements CriteriaInterface
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
        return $model->where('salons.accepted', '1');
    }
}
