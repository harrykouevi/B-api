<?php
/*
 * File name: CommentRepository.php
 * Last modified: 2026.02.18 at 12:51:53
 * Author: harrykouevi - https://github.com/harrykouevi
 * Copyright (c) 2026
 */

namespace App\Repositories;

use App\Models\Comment;
use InfyOm\Generator\Common\BaseRepository;


/**
 * Class CommentRepository
 * @package App\Repositories
 * @version January 19, 2026, 3:59 pm UTC
 *
 * @method Comment findWithoutFail($id, $columns = ['*'])
 * @method Comment find($id, $columns = ['*'])
 * @method Comment first($columns = ['*'])
 */
class CommentRepository extends BaseRepository
{

    /**
     * @var array
     */
    protected $fieldSearchable = [
        'content',
    ];

    /**
     * Configure the Model
     **/
    public function model(): string
    {
        return Comment::class;
    }

  
}


