<?php
/*
 * File name: ServicesTemplateCriteria.php
 * Last modified: 2025.10.27 at 09:09:57
 * Author: harrykouevi - https://github.com/harrykouevi
 * Copyright (c) 2025
 */

namespace App\Criteria\ServiceTemplates;

use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Class ServicesTemplateCriteria.
 *
 * @package namespace App\Criteria\ServiceTemplates;
 */
class ServicesTemplateCriteria implements CriteriaInterface
{
    /**
     * @var ?int
     */
    private ?int $userId;

    /**
     * ServicesTemplateCriteria constructor.
     */
    public function __construct($userId)
    {
        $this->userId = $userId;
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
        if (auth()->check() && auth()->user()->hasRole('admin')) {
            return $model->select('service_templates.*');
        } 
    }
}
