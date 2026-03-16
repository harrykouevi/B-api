<?php
/*
 * File name: AvailableCriteria.php
 * Last modified: 2026.01.21 at 15:21:44
 * Author: harrykouevi - https://github.com/harrykouevi
 * Copyright (c) 2026
 * 
 */

namespace App\Criteria\Stories;

use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;
use Carbon\Carbon;

/**
 * Class AvailableCriteria.
 *
 * @package namespace App\Criteria\Stories;
 */
class AvailableCriteria implements CriteriaInterface
{
    /**
     * Apply criteria in query repository
     *
     * @param $model
     * @param RepositoryInterface $repository
     *
     * @return mixed
     */
    public function apply($model, RepositoryInterface $repository): mixed
    {
        return $model->where('expires_at', '>', Carbon::now());
    }
}
