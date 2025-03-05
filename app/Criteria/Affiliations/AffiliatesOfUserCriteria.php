<?php
/*
 * File name: AffiliatesOfUserCriteria.php
 * Last modified: 2024.04.18 at 18:21:47
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Criteria\Affiliations;

use App\Models\User;
use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Class AffiliatesOfUserCriteria.
 *
 * @package namespace App\Criteria\Options;
 */
class AffiliatesOfUserCriteria implements CriteriaInterface
{

    /**
     * @var ?int
     */
    private ?int $userId;

    /**
     * AffiliatesOfUserCriteria constructor.
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
        if (auth()->check()) {
            return $model->where('affiliates.user_id', $this->userId);
        } else {
            return $model;
        }
    }
}
