<?php
/*
 * File name: BookingsOfStatusesCriteria.php
 * Last modified: 2024.04.18 at 17:41:01
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Criteria\Bookings;

use Illuminate\Http\Request;
use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Class BookingsOfStatusesCriteria.
 *
 * @package namespace App\Criteria\Bookings;
 */
class BookingsOfStatusesCriteria implements CriteriaInterface
{
    /**
     * @var array|Request
     */
    private Request|array $request;

    /**
     * BookingsOfStatusesCriteria constructor.
     * @param Request $request
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
        if (!$this->request->has('statuses')) {
            return $model;
        } else {
            $statuses = $this->request->get('statuses');
            if (in_array('0', $statuses)) { // means all statuses
                return $model;
            }
            return $model->whereIn('booking_status_id', $this->request->get('statuses', []));
        }
    }
}
