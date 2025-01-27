<?php
/*
 * File name: DiscountableRepository.php
 * Last modified: 2024.04.18 at 17:22:50
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Repositories;

use App\Models\Discountable;
use InfyOm\Generator\Common\BaseRepository;

/**
 * Class DiscountableRepository
 * @package App\Repositories
 * @version July 24, 2018, 9:13 pm UTC
 *
 * @method Discountable findWithoutFail($id, $columns = ['*'])
 * @method Discountable find($id, $columns = ['*'])
 * @method Discountable first($columns = ['*'])
 */
class DiscountableRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'custom_field_id',
        'customizable_type'
    ];

    /**
     * Configure the Model
     **/
    public function model(): string
    {
        return Discountable::class;
    }
}
