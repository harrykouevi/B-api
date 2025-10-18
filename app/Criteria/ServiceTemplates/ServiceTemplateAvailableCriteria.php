<?php
/*
 * File name: ServiceTemplateAvailableCriteria.php
 * Last modified: 2025.10.14 at 16:21:44
 * Author: Harry.kouevi
 * Copyright (c) 2025
 */

namespace App\Criteria\EServices;

use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Class ServiceTemplateAvailableCriteria.
 *
 * @package namespace App\Criteria\EServices;
 */
class ServiceTemplateAvailableCriteria implements CriteriaInterface
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
        return $model->where('service_templates.available', '1');
    }
}
