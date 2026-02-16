<?php
/*
 * File name: PostViewRepository.php
 * Last modified: 2026.02.12 at 16:21:53
 * Author: harrykouevi - https://github.com/harrykouevi
 * Copyright (c) 2026
 */

namespace App\Repositories;

use App\Models\Favorite;
use App\Models\PostView;
use InfyOm\Generator\Common\BaseRepository;

/**
 * Class PostViewRepository
 * @package App\Repositories
 * @version January 22, 2021, 8:58 pm UTC
 *
 * @method Favorite findWithoutFail($id, $columns = ['*'])
 * @method Favorite find($id, $columns = ['*'])
 * @method Favorite first($columns = ['*'])
 */
class PostViewRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'post_id',
        'user_id'
    ];

    /**
     * Configure the Model
     **/
    public function model(): string
    {
        return PostView::class;
    }
}
