<?php
/*
 * File name: SlideRepository.php
 * Last modified: 2024.04.18 at 17:21:52
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Repositories;

use App\Models\Slide;
use InfyOm\Generator\Common\BaseRepository;

/**
 * Class SlideRepository
 * @package App\Repositories
 * @version January 25, 2021, 10:54 am UTC
 *
 * @method Slide findWithoutFail($id, $columns = ['*'])
 * @method Slide find($id, $columns = ['*'])
 * @method Slide first($columns = ['*'])
 */
class SlideRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'order',
        'text',
        'button',
        'text_position',
        'text_color',
        'button_color',
        'background_color',
        'indicator_color',
        'image_fit',
        'e_service_id',
        'salon_id',
        'enabled'
    ];

    /**
     * Configure the Model
     **/
    public function model(): string
    {
        return Slide::class;
    }
}
