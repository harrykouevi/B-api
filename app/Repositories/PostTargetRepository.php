<?php
/*
 * File name: PostTargetRepository.php
 * Last modified: 2024.04.18 at 17:21:53
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Repositories;

use App\Models\Post;
use App\Models\PostTarget;
use InfyOm\Generator\Common\BaseRepository;
use Illuminate\Database\Eloquent\Model;


/**
 * Class PostTargetRepository
 * @package App\Repositories
 * @version January 31, 2026, 3:59 pm UTC
 *
 * @method Post findWithoutFail($id, $columns = ['*'])
 * @method Post find($id, $columns = ['*'])
 * @method Post first($columns = ['*'])
 */
class PostTargetRepository extends BaseRepository
{

    /**
     * @var array
     */
    protected $fieldSearchable = [
        
        'post_id',
    ];

    /**
     * Configure the Model
     **/
    public function model(): string
    {
        return PostTarget::class;
    }



}
