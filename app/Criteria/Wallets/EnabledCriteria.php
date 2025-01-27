<?php
/*
 * File name: EnabledCriteria.php
 * Last modified: 2024.04.18 at 17:21:44
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Criteria\Wallets;

use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Class EnabledCriteria.
 *
 * @package namespace App\Criteria\Wallets;
 */
class EnabledCriteria implements CriteriaInterface
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
        return $model->where('wallets.enabled', '1');
    }
}
