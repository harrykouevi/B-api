<?php
/*
 * File name: ServiceTemplateOfCategoriesCriteria.php
 * Last modified: 2025.10.14 at 15:41:01
 * Author: Harry.Kouevi
 * Copyright (c) 2025
 */

namespace App\Criteria\ServiceTemplates;

use Illuminate\Http\Request;
use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Class ServiceTemplateOfCategoriesCriteria.
 *
 * @package namespace App\Criteria\ServiceTemplates;
 */
class ServiceTemplateOfCategoriesCriteria implements CriteriaInterface
{
    /**
     * @var array|Request
     */
    private Request|array $request;

    /**
     * EServicesOfFieldsCriteria constructor.
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
    /**
     * Apply criteria in query repository
     *
     * @param string              $model
     * @param RepositoryInterface $repository
     *
     * @return mixed
     */
    public function apply($model, RepositoryInterface $repository): mixed
    {
        if (!$this->request->has('categories')) {
            return $model;
        } else {
            $categories = $this->request->get('categories');
            if (in_array('0', $categories)) { // means all fields
                return $model;
            }
            return $model->whereIn('category_id', $this->request->get('categories', []));
        }
    }
}
