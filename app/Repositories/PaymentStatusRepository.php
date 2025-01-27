<?php
/*
 * File name: PaymentStatusRepository.php
 * Last modified: 2024.04.18 at 17:21:52
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Repositories;

use App\Models\PaymentStatus;
use InfyOm\Generator\Common\BaseRepository;

/**
 * Class PaymentStatusRepository
 * @package App\Repositories
 * @version January 7, 2021, 4:47 pm UTC
 *
 * @method PaymentStatus findWithoutFail($id, $columns = ['*'])
 * @method PaymentStatus find($id, $columns = ['*'])
 * @method PaymentStatus first($columns = ['*'])
 */
class PaymentStatusRepository extends BaseRepository
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
        return PaymentStatus::class;
    }
}
