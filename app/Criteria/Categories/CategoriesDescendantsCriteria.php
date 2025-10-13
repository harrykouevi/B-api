<?php
/*
 * File name: CategoriesOfSalonCriteria.php
 * Last modified: 2024.04.18 at 17:41:01
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Criteria\Categories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

use Illuminate\Support\Facades\Cache;

/**
 * Class CategoriesOfSalonCriteria.
 *
 * @package namespace App\Criteria\Categories;
 */
class CategoriesDescendantsCriteria implements CriteriaInterface
{
    /**
     * @var array|Request
     */
    private Request|array $request;

    /**
     * CategoriesOfSalonCriteria constructor.
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Apply criteria in query repository
     *
     * @param string              $model
     * @param RepositoryInterface $repository
     *
     * @return Builder
     */
     public function apply($model, RepositoryInterface $repository): Builder
    {
        $withServices = $this->request->boolean('with_services', false);

        // Charger la relation 'media' pour toutes les catégories
        $model = $model->with('media')->orderBy('path');

        // Charger les services si demandé
        if ($withServices) {
            $model = $model->with(['eServices.media', 'eServices.salon']);
        }

        // Retourner la query préparée sans exécuter get()
        return $model;
    }

}
