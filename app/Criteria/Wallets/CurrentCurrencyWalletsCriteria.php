<?php
/*
 * File name: CurrentCurrencyWalletsCriteria.php
 * Last modified: 2024.04.18 at 17:21:43
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Criteria\Wallets;

use DB;
use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Class CurrentCurrencyWalletsCriteria.
 *
 * @package namespace App\Criteria\Options;
 */
class CurrentCurrencyWalletsCriteria implements CriteriaInterface
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
        $currencyCode = DB::raw("json_extract(currency, '$.code')");
        return $model->where($currencyCode, setting('default_currency_code'));
    }
}
