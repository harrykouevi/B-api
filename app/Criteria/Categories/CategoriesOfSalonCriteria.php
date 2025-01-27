<?php
/*
 * File name: CategoriesOfSalonCriteria.php
 * Last modified: 2024.04.18 at 17:41:01
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Criteria\Categories;

use Illuminate\Http\Request;
use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Class CategoriesOfSalonCriteria.
 *
 * @package namespace App\Criteria\Categories;
 */
class CategoriesOfSalonCriteria implements CriteriaInterface
{
    /**
     * @var array|Request
     */
    private Request|array $request;

    /**
     * CategoriesOfSalonCriteria constructor.
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
        if (!$this->request->has('salon_id')) {
            return $model;
        } else {
            $id = $this->request->get('salon_id');
            return $model->join('e_services', 'e_services.category_id', '=', 'categories.id')
                ->where('e_services.salon_id', $id)
                ->select('categories.*')
                ->groupBy('categories.id');
        }
    }
}
