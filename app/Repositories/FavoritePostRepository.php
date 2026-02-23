<?php
/*
 * File name: FavoritePostRepository.php
 * Last modified: 2026.02.13 at 13:21:53
 * Author: harrykouevi - https://github.com/harrykouevi
 * Copyright (c) 2026
 */

namespace App\Repositories;

use App\Models\FavoritePost;
use App\Models\PostView;
use InfyOm\Generator\Common\BaseRepository;

/**
 * Class FavoritePostRepository
 * @package App\Repositories
 * @version January 22, 2021, 8:58 pm UTC
 *
 * @method PostView findWithoutFail($id, $columns = ['*'])
 * @method PostView find($id, $columns = ['*'])
 * @method PostView first($columns = ['*'])
 */
class FavoritePostRepository extends BaseRepository
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
        return FavoritePost::class;
    }
}
