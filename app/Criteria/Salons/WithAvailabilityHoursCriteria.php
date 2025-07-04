<?php
/*
 * File name: OpenedCriteria.php
 * Last modified: 2025.07.03 at 15:41:01
 * Author: harrykouevi - https://github.com/harrykouevi
 * Copyright (c) 2025
 */

namespace App\Criteria\Salons;

use Illuminate\Http\Request;
use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Class OpenedCriteria.
 *
 * @package namespace App\Criteria\Salons;
 */
class WithAvailabilityHoursCriteria implements CriteriaInterface
{
    /**
     * @var array|Request
     */
    private Request|array $request;

    /**
     * OpenedCriteria constructor.
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
    // getClosedAttribute()
        if ($this->request->has('closed')) {
           
            $closed = $this->request->input('closed');
            
            return $model->with('availabilityHours');
        }else{
            return $model;
        }
    }
}
