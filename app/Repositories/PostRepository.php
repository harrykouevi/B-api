<?php
/*
 * File name: PostRepository.php
 * Last modified: 2024.04.18 at 17:21:53
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Repositories;

use App\Models\EService;
use App\Models\Post;
use InfyOm\Generator\Common\BaseRepository;
use Illuminate\Database\Eloquent\Model;


/**
 * Class PostRepository
 * @package App\Repositories
 * @version January 19, 2026, 3:59 pm UTC
 *
 * @method Post findWithoutFail($id, $columns = ['*'])
 * @method Post find($id, $columns = ['*'])
 * @method Post first($columns = ['*'])
 */
class PostRepository extends BaseRepository
{

    /**
     * @var array
     */
    protected $fieldSearchable = [
        'caption',
        'uuid',
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


  

    public function withTargets()
    {
        return $this->with([
            'targets',
            'targets.model' => function ($morph) {
                $morph->morphWith([
                    \App\Models\Eservice::class => [],
                ]);
            }
        ]);
    }

    /**
     * Optionnel : si tu veux une version avec eager loading pour certaines relations par type
     */
    public function withTargetsAndRelations()
    {
        $this->with([
            'targets',
            'targets.model' => function ($morph) {
                $morph->morphWith([
                    \App\Models\Eservice::class => ['salon', 'categories'],
                    // ajouter d’autres modèles et relations si nécessaire
                ]);
            },
        ]);

        return $this;
    }
}


