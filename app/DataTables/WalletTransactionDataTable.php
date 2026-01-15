<?php
/*
 * File name: WalletTransactionDataTable.php
 * Last modified: 2024.04.18 at 17:35:01
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\DataTables;

use App\Models\CustomField;
use App\Models\WalletTransaction;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;

class WalletTransactionDataTable extends DataTable
{
    /**
     * custom fields columns
     * @var array
     */
    public static array $customFields = [];

    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return DataTableAbstract
     */
    public function dataTable(mixed $query): DataTableAbstract
    {
        $dataTable = new EloquentDataTable($query);
        $dataTable->filter(function ($query) {

            // Filtre par type de transaction
            if (request()->has('action_search') && !is_null(request('action_search'))) {
                $query->where('action', request('action_search'));
            }

        });

        $columns = array_column($this->getColumns(), 'data');
        
        return $dataTable
            ->editColumn('updated_at', function ($walletTransaction) {
                return getDateColumn($walletTransaction, 'updated_at');
            })
            ->editColumn('action', function ($walletTransaction) {
                return __("lang.wallet_transaction_$walletTransaction->action");
            })
            ->editColumn('amount', function ($walletTransaction) {
                if ($walletTransaction->action == 'debit') {
                    $walletTransaction->amount = -$walletTransaction->amount;
                }
                return getPriceColumn($walletTransaction, 'amount', isset($walletTransaction->wallet) ? $walletTransaction->wallet->currency : null);
            })
            ->editColumn('payment.id', function ($walletTransaction) {
                if (auth()->user()->hasRole('admin')) {
                    return  getLinksColumnByRouteName([$walletTransaction->payment], 'payments.show', 'id', 'extended_id') ;
                } else {
                    return isset($walletTransaction->payment) ? $walletTransaction->payment->id : "";
                }
            })
            ->editColumn('wallet.name', function ($walletTransaction) {
                if (auth()->user()->hasRole('admin')) {
                    return getLinksColumnByRouteName([$walletTransaction->wallet], 'wallets.edit', 'id', 'extended_name');
                } else {
                    return isset($walletTransaction->wallet) ? $walletTransaction->wallet->name : "";
                }
            })
            ->editColumn('user.name', function ($walletTransaction) {
                if (auth()->user()->hasRole('admin')) {
                    return getLinksColumnByRouteName([$walletTransaction->user], 'users.edit', 'id', 'name');
                } else {
                    return isset($walletTransaction->user) ? $walletTransaction->user->name : "";
                }
            })
            ->rawColumns(array_merge($columns));
    }

    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns(): array
    {
        $columns = [
            [
                'data' => 'amount',
                'title' => trans('lang.wallet_transaction_amount'),

            ],
            [
                'data' => 'description',
                'title' => trans('lang.wallet_transaction_description'),

            ],
            [
                'data' => 'action',
                'title' => trans('lang.wallet_transaction_action'),
            ],
                 [
                'data' => 'payment.id',
                'title' => trans('lang.payment'),

            ],
            [
                'data' => 'wallet.name',
                'title' => trans('lang.wallet_transaction_wallet_id'),

            ],
            [
                'data' => 'user.name',
                'title' => trans('lang.wallet_transaction_user_id'),

            ],
            [
                'data' => 'updated_at',
                'title' => trans('lang.wallet_transaction_updated_at'),
                'searchable' => false,
            ]
        ];

        $hasCustomField = in_array(WalletTransaction::class, setting('custom_field_models', []));
        if ($hasCustomField) {
            $customFieldsCollection = CustomField::where('custom_field_model', WalletTransaction::class)->where('in_table', '=', true)->get();
            foreach ($customFieldsCollection as $key => $field) {
                array_splice($columns, $field->order - 1, 0, [[
                    'data' => 'custom_fields.' . $field->name . '.view',
                    'title' => trans('lang.wallet_transaction_' . $field->name),
                    'orderable' => false,
                    'searchable' => false,
                ]]);
            }
        }
        return $columns;
    }

    /**
     * Get query source of dataTable.
     *
     * @param WalletTransaction $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(WalletTransaction $model): \Illuminate\Database\Eloquent\Builder
    {
        if (auth()->check() && !auth()->user()->hasRole('admin')) {
            return $model->newQuery()->with("wallet")->with("user")->with("payment")
                ->join('wallets', 'wallets.id', '=', 'wallet_transactions.wallet_id')
                ->where('wallets.user_id', auth()->id())
                ->select("$model->table.*")
                ->orderBy('wallet_transactions.payment_id', 'desc');
        } else {
            return $model->newQuery()->with("wallet")->with("user")->with("payment")
                ->select("$model->table.*")
                ->orderBy('wallet_transactions.payment_id', 'desc');
        //  }
        }
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return Builder
     */
    public function html(): Builder
    {
        return $this->builder()
            ->columns($this->getColumns())
            ->setTableId('wallet-transactions-table')
            ->minifiedAjax()
            ->parameters(array_merge(
                config('datatables-buttons.parameters'), [
                    'language' => json_decode(
                        file_get_contents(base_path('resources/lang/' . app()->getLocale() . '/datatable.json')
                        ), true),
                    'searching' => false, // désactive recherche globale
                    'processing' => true,
                    'serverSide' => true,
                ]
            ));
    }

    /**
     * Export PDF using DOMPDF
     * @return mixed
     */
    public function pdf(): mixed
    {
        $data = $this->getDataForPrint();
        $pdf = PDF::loadView($this->printPreview, compact('data'));
        return $pdf->download($this->filename() . '.pdf');
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename(): string
    {
        return 'wallet_transactionsdatatable_' . time();
    }
}
