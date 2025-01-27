<?php
/*
 * File name: AvailabilityHourRepository.php
 * Last modified: 2024.04.18 at 17:21:53
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Repositories;

use App\Models\AvailabilityHour;
use InfyOm\Generator\Common\BaseRepository;

/**
 * Class AvailabilityHourRepository
 * @package App\Repositories
 * @version January 16, 2021, 4:08 pm UTC
 *
 * @method AvailabilityHour findWithoutFail($id, $columns = ['*'])
 * @method AvailabilityHour find($id, $columns = ['*'])
 * @method AvailabilityHour first($columns = ['*'])
 */
class AvailabilityHourRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'day',
        'start_at',
        'end_at',
        'data',
        'salon_id'
    ];

    /**
     * Configure the Model
     **/
    public function model(): string
    {
        return AvailabilityHour::class;
    }
}
