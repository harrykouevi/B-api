<?php
/*
 * File name: WalletTransactionsOfUserCriteria.php
 * Last modified: 2024.04.18 at 18:21:47
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Criteria\Wallets;

use App\Models\User;
use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Class WalletsOfUserCriteria.
 *
 * @package namespace App\Criteria\Options;
 */
class WalletTransactionsOfUserCriteria implements CriteriaInterface
{

    /**
     * @var ?int
     */
    private ?int $userId;

    /**
     * @var ?int
     */
    private ?int $paymentId;

    /**
     * WalletsOfUserCriteria constructor.
     */
    public function __construct($userId , int $paymentId = Null)
    {
        $this->userId = $userId;
        $this->paymentId = $paymentId;
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
        if (auth()->check() && !auth()->user()->hasRole('admin')) {
            return $model->join('wallets', 'wallets.id', '=', 'wallet_transactions.wallet_id')
                ->where('wallets.user_id', $this->userId)
                ->where('wallets.enabled', 1)
                ->select('wallet_transactions.*');

        } else if (auth()->user()->hasRole('customer') || auth()->user()->hasRole('salon owner')) {
            
            $model = $model->where('wallet_transactions.user_id', $this->userId) ;
            if(!is_null($this->paymentId)) $model->where('wallet_transactions.user_id', $this->userId) ;

            return $model->select('wallet_transactions.*');
            
        } else {
            return $model;
        }
    }
}
