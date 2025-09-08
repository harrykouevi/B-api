<?php
/*
 * File name: PurchaseRepository.php
 * Last modified: 2024.04.18 at 17:21:52
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Repositories;

use App\Models\Purchase;
use InfyOm\Generator\Common\BaseRepository;

/**
 * Class PurchaseRepository
 * @package App\Repositories
 * @version January 25, 2021, 9:22 pm UTC
 *
 * @method Purchase findWithoutFail($id, $columns = ['*'])
 * @method Purchase find($id, $columns = ['*'])
 * @method Purchase first($columns = ['*'])
 */
class PurchaseRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'salon',
        'e_service',
        'user_id',
        'purchase_status_id',
        'payment_id',
        'coupon',
        'taxes',
        'purchase_at',
        'hint'
    ];

    /**
     * Configure the Model
     **/
    public function model(): string
    {
        return Purchase::class;
    }

   
}
