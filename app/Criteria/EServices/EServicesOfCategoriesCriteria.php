<?php
/*
 * File name: EServicesOfCategoriesCriteria.php
 * Last modified: 2024.04.18 at 17:41:01
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Criteria\EServices;

use Illuminate\Http\Request;
use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Class EServicesOfCategoriesCriteria.
 *
 * @package namespace App\Criteria\EServices;
 */
class EServicesOfCategoriesCriteria implements CriteriaInterface
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
