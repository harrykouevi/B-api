<?php
/*
 * File name: UnReadCriteria.php
 * Last modified: 2024.04.18 at 17:21:42
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Criteria\Notifications;

use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Class UnReadCriteria.
 *
 * @package namespace App\Criteria\Notifications;
 */
class UnReadCriteria implements CriteriaInterface
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
        return $model->where('read_at', null);
    }
}
