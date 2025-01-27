<?php
/*
 * File name: TaxDataTable.php
 * Last modified: 2024.04.18 at 17:53:30
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\DataTables;

use App\Models\CustomField;
use App\Models\Tax;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;

class TaxDataTable extends DataTable
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
            ->editColumn('name', function ($tax) {
                return $tax->name;
            })
            ->editColumn('updated_at', function ($tax) {
                return getDateColumn($tax, 'updated_at');
            })
            ->editColumn('value', function ($tax) {
                if ($tax['type'] == 'percent') {
                    return $tax['value'] . "%";
                } else {
                    return getPriceColumn($tax, 'value');
                }
            })
            ->editColumn('type', function ($tax) {
                return "<span class='badge bg-" . setting('theme_color') . "'>" . trans('lang.tax_' . $tax['type']) . "</span>";
            })
            ->addColumn('action', 'settings.taxes.datatables_actions')
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
                'title' => trans('lang.tax_name'),

            ],
            [
                'data' => 'value',
                'title' => trans('lang.tax_value'),

            ],
            [
                'data' => 'type',
                'title' => trans('lang.tax_type'),

            ],
            [
                'data' => 'updated_at',
                'title' => trans('lang.tax_updated_at'),
                'searchable' => false,
            ]
        ];

        $hasCustomField = in_array(Tax::class, setting('custom_field_models', []));
        if ($hasCustomField) {
            $customFieldsCollection = CustomField::where('custom_field_model', Tax::class)->where('in_table', '=', true)->get();
            foreach ($customFieldsCollection as $key => $field) {
                array_splice($columns, $field->order - 1, 0, [[
                    'data' => 'custom_fields.' . $field->name . '.view',
                    'title' => trans('lang.tax_' . $field->name),
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
     * @param Tax $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Tax $model): \Illuminate\Database\Eloquent\Builder
    {
        return $model->newQuery()->select("$model->table.*");
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
            ->addAction(['width' => '80px', 'printable' => false, 'responsivePriority' => '100'])
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
        return 'taxesdatatable_' . time();
    }
}
