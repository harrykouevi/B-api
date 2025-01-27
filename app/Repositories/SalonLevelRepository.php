<?php
/*
 * File name: SalonLevelRepository.php
 * Last modified: 2024.04.18 at 17:21:52
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Repositories;

use App\Models\SalonLevel;
use InfyOm\Generator\Common\BaseRepository;

/**
 * Class SalonLevelRepository
 * @package App\Repositories
 * @version January 13, 2021, 10:56 am UTC
 *
 * @method SalonLevel findWithoutFail($id, $columns = ['*'])
 * @method SalonLevel find($id, $columns = ['*'])
 * @method SalonLevel first($columns = ['*'])
 */
class SalonLevelRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'name',
        'commission',
        'disabled',
        'default'
    ];

    /**
     * Configure the Model
     **/
    public function model(): string
    {
        return SalonLevel::class;
    }
}
