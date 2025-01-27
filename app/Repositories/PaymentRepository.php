<?php
/*
 * File name: PaymentRepository.php
 * Last modified: 2024.04.18 at 17:21:52
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Repositories;

use App\Models\Payment;
use InfyOm\Generator\Common\BaseRepository;

/**
 * Class PaymentRepository
 * @package App\Repositories
 * @version January 7, 2021, 4:54 pm UTC
 *
 * @method Payment findWithoutFail($id, $columns = ['*'])
 * @method Payment find($id, $columns = ['*'])
 * @method Payment first($columns = ['*'])
 */
class PaymentRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'amount',
        'description',
        'user_id',
        'payment_method_id',
        'payment_status_id'
    ];

    /**
     * Configure the Model
     **/
    public function model(): string
    {
        return Payment::class;
    }
}
