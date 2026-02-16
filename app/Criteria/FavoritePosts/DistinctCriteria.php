<?php
/*
 * File name: DistinctCriteria.php
 * Last modified: 2026.02.12 at 17:21:43
 * Author:  harrykouevi - https://github.com/harrykouevi
 * Copyright (c) 2026
 */

namespace App\Criteria\FavoritePosts;

use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Class DistinctCriteria.
 *
 * @package namespace App\Criteria\Favorites;
 */
class DistinctCriteria implements CriteriaInterface
{
    /**
     * Apply criteria in query repository
     *
     * @param string $model
     * @param RepositoryInterface $repository
     *
     * @return mixed
     */
    public function apply($model, RepositoryInterface $repository): mixed
    {
        return $model->groupBy('post_id');
    }
}
