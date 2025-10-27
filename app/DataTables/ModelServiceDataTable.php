<?php
/*
 * File name: ServiceTemplateDataTable.php
 * Last modified: 2024.04.18 at 17:53:30
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\DataTables;

use App\Models\CustomField;
use App\Models\ServiceTemplate;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;

class ModelServiceDataTable extends DataTable
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
            if (request()->has('search') && (!is_null(request('search')['value']) || request('search')['value'] != '')) {
                $search = request('search')['value'] ;
                $columns = $this->getColumns();

                $query->where(function ($q) use ($columns, $search) {
                    foreach ($columns as $column) {
                        if ($column['searchable'] ?? false) {
                            if (str_contains($column['name'], '.')) {
                                $parts = explode('.', $column['name']);
                                $colName = $parts[0] . '.' . $parts[1]; 
                            } else {
                                $colName = $column['name'];
                            }
                            $q->orWhere($colName, 'like', "%{$search}%");
                        }
                    }
                });
            }

        });
        $columns = array_column($this->getColumns(), 'data');
        return $dataTable
            ->editColumn('id', function ($eService) {
                return  "<span style=' color: #a0bdd7; font-family: fangsong;'>" . $eService->id . "</span>";
            })
            ->editColumn('image', function ($eService) {
                return getMediaColumn($eService, 'image');
            })
            ->editColumn('name', function ($eService) {
                return $eService['name']  ;
            })
            // ->editColumn('price', function ($eService) {
            //     return getPriceColumn($eService);
            // })
            // ->editColumn('discount_price', function ($eService) {
            //     if (empty($eService['discount_price'])) {
            //         return '-';
            //     } else {
            //         return getPriceColumn($eService, 'discount_price');
            //     }
            // })
            ->editColumn('updated_at', function ($eService) {
                return getDateColumn($eService, 'updated_at');
            })
            // ->editColumn('categories', function ($eService) {
            //     return getLinksColumnByRouteName($eService->categories, 'categories.edit', 'id', 'name');
            // })
            ->editColumn('category.path_names', function ($eService) {
                return getLinksColumnByRouteName([$eService->category], 'categories.edit', 'id', 'path_names');
            })
            // ->editColumn('available', function ($eService) {
            //     return getBooleanColumn($eService, 'available');
            // })
            ->addColumn('action', 'service_templates.datatables_actions')
            ->rawColumns(array_merge($columns, ['action']))
            //  ->rawColumns(array_merge($columns))
            ;

        
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
                'data' => 'id',
                'title' => '#',

            ],
            [
                'data' => 'image',
                'title' => trans('lang.e_service_image'),
                'searchable' => false, 'orderable' => false, 'exportable' => false, 'printable' => false,
            ],
            [
                'data' => 'name',
                'name' => 'service_templates.name',
                'title' => trans('lang.e_service_name'),
                'searchable' => true,

            ],
            // [
            //     'data' => 'salon.name',
            //     'name' => 'salon.name',
            //     'title' => trans('lang.e_service_salon_id'),

            // ],
            // [
            //     'data' => 'price',
            //     'title' => trans('lang.e_service_price'),

            // ],
            // [
            //     'data' => 'discount_price',
            //     'title' => trans('lang.e_service_discount_price'),

            // ],
            [
                'data' => 'category.path_names',
                'name' => 'categories.path_names',
                'title' => trans('lang.e_service_categories'),
                'searchable' => true,
                'orderable' => true
            ],
            // [
            //     'data' => 'available',
            //     'title' => trans('lang.e_service_available'),

            // ],
            // [
            //     'data' => 'updated_at',
            //     'title' => trans('lang.e_service_updated_at'),
            //     'searchable' => false,
            // ],
            [
                'data' => 'updated_at',
                'title' => trans('lang.user_updated_at'),
                'searchable' => false,
            ]
        ];

        $hasCustomField = in_array(ServiceTemplate::class, setting('custom_field_models', []));
        if ($hasCustomField) {
            $customFieldsCollection = CustomField::where('custom_field_model', ServiceTemplate::class)->where('in_table', '=', true)->get();
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
     * @param ServiceTemplate $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(ServiceTemplate $model): \Illuminate\Database\Eloquent\Builder
    {
        if (auth()->user()->hasRole('salon owner')) {
            return $model->newQuery()->join('salon_users', 'salon_users.salon_id', '=', 'service_templates.salon_id')
                    ->with('category')
                ->leftJoin('categories', 'categories.id', '=', 'service_templates.category_id')
                ->groupBy('service_templates.id')
                ->where('salon_users.user_id', auth()->id())
                ->select('service_templates.*');
        }
        return $model->newQuery()
        ->with("category")
        ->leftJoin('categories', 'categories.id', '=', 'service_templates.category_id')
        ->select("$model->table.*");
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
                        ), true),
                    'fixedColumns' => [],
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
        return 'service_templatesdatatable_' . time();
    }
}
