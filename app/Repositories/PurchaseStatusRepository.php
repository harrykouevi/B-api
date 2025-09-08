<?php
/*
 * File name: PurchaseStatusRepository.php
 * Last modified: 2024.04.18 at 17:22:51
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Repositories;

use App\Models\PurchaseStatus;
use InfyOm\Generator\Common\BaseRepository;

/**
 * Class PurchaseStatusRepository
 * @package App\Repositories
 * @version January 25, 2021, 7:18 pm UTC
 *
 * @method PurchaseStatus findWithoutFail($id, $columns = ['*'])
 * @method PurchaseStatus find($id, $columns = ['*'])
 * @method PurchaseStatus first($columns = ['*'])
 */
class PurchaseStatusRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'status',
        'order'
    ];

    /**
     * Configure the Model
     **/
    public function model(): string
    {
        return PurchaseStatus::class;
    }
}
