<?php
/*
 * File name: ExperienceRepository.php
 * Last modified: 2024.04.18 at 17:21:54
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Repositories;

use App\Models\Experience;
use InfyOm\Generator\Common\BaseRepository;

/**
 * Class ExperienceRepository
 * @package App\Repositories
 * @version January 12, 2021, 11:16 am UTC
 *
 * @method Experience findWithoutFail($id, $columns = ['*'])
 * @method Experience find($id, $columns = ['*'])
 * @method Experience first($columns = ['*'])
 */
class ExperienceRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'title',
        'description',
        'salon_id'
    ];

    /**
     * Configure the Model
     **/
    public function model(): string
    {
        return Experience::class;
    }
}
