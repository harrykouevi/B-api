<?php


/*
 * File name: CurrencyDataTable.php
 * Last modified: 2024.04.18 at 17:53:30
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\DataTables;

use App\Models\Currency;
use App\Models\CustomField;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;

class CurrencyDataTable extends DataTable
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
        $columns = array_column($this->getColumns(), 'data');
        $dataTable = $dataTable
            ->editColumn('updated_at', function ($currency) {
                return getDateColumn($currency, 'updated_at');
            })
            ->editColumn('name', function ($currency) {
                return $currency->name;
            })
            ->editColumn('symbol', function ($currency) {
                return $currency->symbol;
            })
            ->editColumn('code', function ($currency) {
                return $currency->code;
            })
            ->addColumn('action', 'settings.currencies.datatables_actions')
            ->rawColumns(array_merge($columns, ['action']));

        return $dataTable;
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
                'data' => 'name',
                'title' => trans('lang.currency_name'),

            ],
            [
                'data' => 'symbol',
                'title' => trans('lang.currency_symbol'),

            ],
            [
                'data' => 'code',
                'title' => trans('lang.currency_code'),

            ],
            [
                'data' => 'decimal_digits',
                'title' => trans('lang.currency_decimal_digits'),

            ],
            [
                'data' => 'rounding',
                'title' => trans('lang.currency_rounding'),

            ],
            [
                'data' => 'updated_at',
                'title' => trans('lang.currency_updated_at'),
                'searchable' => false,
            ]
        ];

        $hasCustomField = in_array(Currency::class, setting('custom_field_models', []));
        if ($hasCustomField) {
            $customFieldsCollection = CustomField::where('custom_field_model', Currency::class)->where('in_table', '=', true)->get();
            foreach ($customFieldsCollection as $key => $field) {
                array_splice($columns, $field->order - 1, 0, [[
                    'data' => 'custom_fields.' . $field->name . '.view',
                    'title' => trans('lang.currency_' . $field->name),
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
     * @param Currency $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Currency $model): \Illuminate\Database\Eloquent\Builder
    {
        return $model->newQuery()->select("currencies.*");
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
            ->minifiedAjax()
            ->addAction(['title'=>trans('lang.actions'),'width' => '80px', 'printable' => false, 'responsivePriority' => '100'])
            ->parameters(array_merge(
                config('datatables-buttons.parameters'), [
                    'language' => json_decode(
                        file_get_contents(base_path('resources/lang/' . app()->getLocale() . '/datatable.json')
                        ), true)
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
        return 'currenciesdatatable_' . time();
    }
}
