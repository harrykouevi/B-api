<?php
/*
 * File name: PostRepository.php
 * Last modified: 2024.04.18 at 17:21:53
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Repositories;

use App\Models\Post;
use InfyOm\Generator\Common\BaseRepository;
use Prettus\Repository\Contracts\CacheableInterface;
use Prettus\Repository\Traits\CacheableRepository;

/**
 * Class PostRepository
 * @package App\Repositories
 * @version January 19, 2026, 3:59 pm UTC
 *
 * @method Post findWithoutFail($id, $columns = ['*'])
 * @method Post find($id, $columns = ['*'])
 * @method Post first($columns = ['*'])
 */
class PostRepository extends BaseRepository implements  CacheableInterface
{

 use CacheableRepository;   
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'description',
        'salon_id',
        'user_id',
    ];

    /**
     * Configure the Model
     **/
    public function model(): string
    {
        return Post::class;
    }

    /**
     * @return array
     */
    public function groupedBySalons(): array
    {
        $eServices = [];
        foreach ($this->all() as $model) {
            if (!empty($model->salon)) {
                $eServices[$model->salon->name][$model->id] = $model->name;
            }
        }
        return $eServices;
    }
}
