<?php
/*
 * File name: HiddenCriteria.php
 * Last modified: 2024.04.18 at 17:30:50
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Criteria\Categories;

use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Class HiddenCriteria.
 *
 * @package namespace App\Criteria\Categories;
 */
class HiddenCriteria implements CriteriaInterface
{
    private array $hidden = [];

    /**
     * HiddenCriteria constructor.
     * @param array $hidden
     */
    public function __construct(array $hidden)
    {
        $this->hidden = $hidden;
    }

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
        return $repository->hidden($this->hidden);
    }
}
