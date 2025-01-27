<?php
/*
 * File name: GalleryRepository.php
 * Last modified: 2024.04.18 at 17:22:50
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Repositories;

use App\Models\Gallery;
use InfyOm\Generator\Common\BaseRepository;

/**
 * Class GalleryRepository
 * @package App\Repositories
 * @version January 23, 2021, 8:15 pm UTC
 *
 * @method Gallery findWithoutFail($id, $columns = ['*'])
 * @method Gallery find($id, $columns = ['*'])
 * @method Gallery first($columns = ['*'])
 */
class GalleryRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'description',
        'salon_id'
    ];

    /**
     * Configure the Model
     **/
    public function model(): string
    {
        return Gallery::class;
    }
}
