<?php
/*
 * File name: CustomFieldValueRepository.php
 * Last modified: 2024.04.18 at 17:21:52
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Repositories;

use App\Models\CustomFieldValue;
use InfyOm\Generator\Common\BaseRepository;

/**
 * Class CustomFieldValueRepository
 * @package App\Repositories
 * @version July 24, 2018, 9:13 pm UTC
 *
 * @method CustomFieldValue findWithoutFail($id, $columns = ['*'])
 * @method CustomFieldValue find($id, $columns = ['*'])
 * @method CustomFieldValue first($columns = ['*'])
 */
class CustomFieldValueRepository extends BaseRepository
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
        return CustomFieldValue::class;
    }
}
