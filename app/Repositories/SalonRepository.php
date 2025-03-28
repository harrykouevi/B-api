<?php
/*
 * File name: SalonRepository.php
 * Last modified: 2024.04.18 at 17:22:50
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Repositories;

use App\Models\Salon;
use InfyOm\Generator\Common\BaseRepository;

/**
 * Class SalonRepository
 * @package App\Repositories
 * @version January 13, 2021, 11:11 am UTC
 *
 * @method Salon findWithoutFail($id, $columns = ['*'])
 * @method Salon find($id, $columns = ['*'])
 * @method Salon first($columns = ['*'])
 */
class SalonRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'name',
        'salon_level_id',
        'address_id',
        'description',
        'phone_number',
        'mobile_number',
        'availability_range',
        'available',
        'closed',
        'featured'
    ];

    /**
     * Configure the Model
     **/
    public function model(): string
    {
        return Salon::class;
    }
}
