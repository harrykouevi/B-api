<?php

namespace App\Repositories;

use App\Models\TestPost;
use InfyOm\Generator\Common\BaseRepository;
use Prettus\Repository\Contracts\CacheableInterface;
use Prettus\Repository\Traits\CacheableRepository;


/**
 * 
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
        return TestPost::class;
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