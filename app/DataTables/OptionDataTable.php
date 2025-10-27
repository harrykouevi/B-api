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
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;

class OptionDataTable extends DataTable
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
            ->editColumn('e_service.name', function ($option) {
                return getLinksColumnByRouteName([$option->eService], 'eServices.edit', 'id', 'name');
            })
            ->editColumn('option_group.name', function ($option) {
                return getLinksColumnByRouteName([$option->optionGroup], 'optionGroups.edit', 'id', 'name');
            })
            ->editColumn('e_service.salon.name', function ($option) {
                if (isset($option->eService))
                    return getLinksColumnByRouteName([$option->eService->salon], 'salons.edit', 'id', 'name');
                else
                    return "";
            })
            ->editColumn('updated_at', function ($option) {
                return getDateColumn($option);
            })
            ->addColumn('action', 'options.datatables_actions')
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
                'name' => 'options.name',
                'title' => trans('lang.option_name'),
                'searchable' => true,

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
               
                'data' => 'e_service.name',
                'name' => 'e_services.name',
                'title' => trans('lang.e_service'),
                'searchable' => true,


            ],
            [
                'data' => 'e_service.salon.name',
                'name' => 'eService.salon.name',
                'title' => trans('lang.salon'),

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
     * @param Option $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Option $model): \Illuminate\Database\Eloquent\Builder
    {
        if (auth()->user()->hasRole('admin')) {
            return $model->newQuery()->with("eService")->with("optionGroup")->with('eService.salon')
            ->leftJoin("e_services", "options.e_service_id", "=", "e_services.id")
            ->leftJoin('option_groups', 'option_groups.id', '=', 'e_services.option_group_id')
  
            ->select("$model->table.*");
       
        } else if (auth()->user()->hasRole('salon owner')) {
            return $model->newQuery()->with("eService")->with("optionGroup")->with('eService.salon')
                ->join("e_services", "options.e_service_id", "=", "e_services.id")
                ->leftJoin('option_groups', 'option_groups.id', '=', 'e_services.option_group_id')
                
                ->join("salon_users", "e_services.salon_id", "=", "salon_users.salon_id")
                ->where('salon_users.user_id', auth()->id())
                ->groupBy("options.id")
                ->select("$model->table.*");
        } else {
            return $model->newQuery()->with("eService")->with("optionGroup")->with('eService.salon')->select("$model->table.*");
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
