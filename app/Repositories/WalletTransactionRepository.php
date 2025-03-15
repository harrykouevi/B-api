<?php
/*
 * File name: WalletTransactionRepository.php
 * Last modified: 2024.04.18 at 17:22:50
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Repositories;

use App\Models\WalletTransaction;
use InfyOm\Generator\Common\BaseRepository;

/**
 * Class WalletTransactionRepository
 * @package App\Repositories
 * @version August 8, 2021, 3:57 pm CEST
 *
 * @method WalletTransaction findWithoutFail($id, $columns = ['*'])
 * @method WalletTransaction find($id, $columns = ['*'])
 * @method WalletTransaction first($columns = ['*'])
 */
class WalletTransactionRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'amount',
        'description',
        'action',
        'wallet_id',
        'payment_id',
        'user_id'
    ];

    /**
     * Configure the Model
     **/
    public function model(): string
    {
        return WalletTransaction::class;
    }
}
