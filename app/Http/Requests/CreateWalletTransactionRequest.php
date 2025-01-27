<?php
/*
 * File name: CreateWalletTransactionRequest.php
 * Last modified: 2024.04.18 at 17:21:52
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Http\Requests;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Http\FormRequest;

class CreateWalletTransactionRequest extends FormRequest
{

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        if ($this->get('action') == 'debit') {
            $wallet = Wallet::find($this->get('wallet_id'));
            $max = isset($wallet) ? $wallet->balance : 0;
            WalletTransaction::$rules['amount'] = "required|numeric|min:0.01|max:$max";
        }
        return WalletTransaction::$rules;
    }
}
