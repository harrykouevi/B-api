<?php
/*
 * File name: EServiceDataTable.php
 * Last modified: 2024.04.18 at 17:53:30
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\DataTables;

use App\Models\CustomField;
use App\Models\EService;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;

class EServiceDataTable extends DataTable
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
            ->editColumn('image', function ($eService) {
                return getMediaColumn($eService, 'image');
            })
            ->editColumn('name', function ($eService) {
                if ($eService['featured']) {
                    return $eService['name'] . "<span class='badge bg-" . setting('theme_color') . " p-1 m-2'>" . trans('lang.e_service_featured') . "</span>";
                }
                return $eService['name'];
            })
            ->editColumn('price', function ($eService) {
                return getPriceColumn($eService);
            })
            ->editColumn('discount_price', function ($eService) {
                if (empty($eService['discount_price'])) {
                    return '-';
                } else {
                    return getPriceColumn($eService, 'discount_price');
                }
            })
            ->editColumn('updated_at', function ($eService) {
                return getDateColumn($eService, 'updated_at');
            })
            ->editColumn('categories', function ($eService) {
                return getLinksColumnByRouteName($eService->categories, 'categories.edit', 'id', 'name');
            })
            ->editColumn('salon.name', function ($eService) {
                return getLinksColumnByRouteName([$eService->salon], 'salons.edit', 'id', 'name');
            })
            ->editColumn('available', function ($eService) {
                return getBooleanColumn($eService, 'available');
            })
            ->addColumn('action', 'e_services.datatables_actions')
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
                'data' => 'image',
                'title' => trans('lang.e_service_image'),
                'searchable' => false, 'orderable' => false, 'exportable' => false, 'printable' => false,
            ],
            [
                'data' => 'name',
                'title' => trans('lang.e_service_name'),

            ],
            [
                'data' => 'salon.name',
                'name' => 'salon.name',
                'title' => trans('lang.e_service_salon_id'),

            ],
            [
                'data' => 'price',
                'title' => trans('lang.e_service_price'),

            ],
            [
                'data' => 'discount_price',
                'title' => trans('lang.e_service_discount_price'),

            ],
            [
                'data' => 'categories',
                'title' => trans('lang.e_service_categories'),
                'searchable' => false,
                'orderable' => false
            ],
            [
                'data' => 'available',
                'title' => trans('lang.e_service_available'),

            ],
            [
                'data' => 'updated_at',
                'title' => trans('lang.e_service_updated_at'),
                'searchable' => false,
            ]
        ];

        $hasCustomField = in_array(EService::class, setting('custom_field_models', []));
        if ($hasCustomField) {
            $customFieldsCollection = CustomField::where('custom_field_model', EService::class)->where('in_table', '=', true)->get();
            foreach ($customFieldsCollection as $key => $field) {
                array_splice($columns, $field->order - 1, 0, [[
                    'data' => 'custom_fields.' . $field->name . '.view',
                    'title' => trans('lang.e_service_' . $field->name),
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
     * @param EService $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(EService $model): \Illuminate\Database\Eloquent\Builder
    {
        if (auth()->user()->hasRole('salon owner')) {
            return $model->newQuery()->with("salon")->join('salon_users', 'salon_users.salon_id', '=', 'e_services.salon_id')
                ->groupBy('e_services.id')
                ->where('salon_users.user_id', auth()->id())
                ->select('e_services.*');
        }
        return $model->newQuery()->with("salon")->select("$model->table.*");
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
        return 'e_servicesdatatable_' . time();
    }
}
