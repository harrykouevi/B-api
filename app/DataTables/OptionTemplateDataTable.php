<?php
/*
 * File name: OptionDataTable.php
 * Last modified: 2024.04.18 at 17:35:01
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\DataTables;

use App\Models\CustomField;
use App\Models\Option;
use App\Models\OptionTemplate;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;

class OptionTemplateDataTable extends DataTable
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
            ->editColumn('name', function ($option) {
                return $option->name;
            })
            ->editColumn('image', function ($option) {
                return getMediaColumn($option, 'image','','');
            })
            ->editColumn('price', function ($option) {
                return getPriceColumn($option);
            })
            ->editColumn('service_template.name', function ($option) {
                return getLinksColumnByRouteName([$option->serviceTemplate], 'model-services.edit', 'id', 'name');
            })
            ->editColumn('option_group.name', function ($option) {
                return getLinksColumnByRouteName([$option->optionGroup], 'optionGroups.edit', 'id', 'name');
            })
           
            ->editColumn('updated_at', function ($option) {
                return getDateColumn($option);
            })
            ->addColumn('action', 'option_templates.datatables_actions')
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
                'title' => trans('lang.option_name'),

            ],
            [
                'data' => 'image',
                'title' => trans('lang.option_image'),
                'searchable' => false, 'orderable' => false, 'exportable' => false, 'printable' => false,
            ],
            [
                'data' => 'price',
                'title' => trans('lang.option_price'),

            ],
            [
                'data' => 'service_template.name',
                'name' => 'eService.name',
                'title' => trans('lang.model_service'),

            ],
            
            [
                'data' => 'option_group.name',
                'name' => 'optionGroup.name',
                'title' => trans('lang.option_group'),

            ],
            [
                'data' => 'updated_at',
                'title' => trans('lang.option_updated_at'),
                'searchable' => false,
            ]
        ];

        $hasCustomField = in_array(Option::class, setting('custom_field_models', []));
        if ($hasCustomField) {
            $customFieldsCollection = CustomField::where('custom_field_model', Option::class)->where('in_table', '=', true)->get();
            foreach ($customFieldsCollection as $key => $field) {
                array_splice($columns, $field->order - 1, 0, [[
                    'data' => 'custom_fields.' . $field->name . '.view',
                    'title' => trans('lang.option_' . $field->name),
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
     * @param OptionTemplate $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(OptionTemplate $model): \Illuminate\Database\Eloquent\Builder
    {
        if (auth()->user()->hasRole('admin')) {
            return $model->newQuery()->with("serviceTemplate")->select("$model->table.*");
        } else if (auth()->user()->hasRole('salon owner')) {
            return $model->newQuery()->with("serviceTemplate")
                ->join("service_templates", "options_templates.service_template_id", "=", "service_templates.id")
                ->groupBy("options_templates.id")
                ->select("$model->table.*");
        } else {
            return $model->newQuery()->with("serviceTemplate")->select("$model->table.*");
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
            ->minifiedAjax()
            ->addAction(['title' => trans('lang.actions'), 'width' => '80px', 'printable' => false, 'responsivePriority' => '100'])
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
        return 'optionsdatatable_' . time();
    }
}
