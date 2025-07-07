<?php
/*
 * File name: NearCriteria.php
 * Last modified: 2024.04.18 at 17:41:01
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Criteria\Salons;

use Illuminate\Http\Request;
use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Class NearCriteria.
 *
 * @package namespace App\Criteria\Salons;
 */
class NearCriteria implements CriteriaInterface
{
    /**
     * @var array|Request
     */
    private Request|array $request;

    /**
     * NearCriteria constructor.
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
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
        if ($this->request->has(['myLat', 'myLon', 'areaLat', 'areaLon'])) {
            $coordination = $this->request->only('myLat', 'myLon', 'areaLat', 'areaLon');
            $coordination = array_values($coordination);
            return $model->near(...$coordination);
        } else if ($this->request->has(['myLat', 'myLon'])) {
            $coordination = $this->request->only('myLat', 'myLon');
            $coordination = array_values($coordination); 
            array_push($coordination, ...$coordination);
            return $model->near(...$coordination);
        } else {
            return $model->orderBy('available');
        }
    }
}
