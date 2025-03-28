<?php
/*
 * File name: AwardDataTable.php
 * Last modified: 2024.04.18 at 17:30:50
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\DataTables;

use App\Models\Award;
use App\Models\CustomField;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;

class AwardDataTable extends DataTable
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
            ->editColumn('updated_at', function ($award) {
                return getDateColumn($award, 'updated_at');
            })
            ->editColumn('title', function ($award) {
                return $award->title;
            })
            ->editColumn('description', function ($award) {
                return getStripedHtmlColumn($award, 'description');
            })
            ->editColumn('salon.name', function ($award) {
                return getLinksColumnByRouteName([$award->salon], 'salons.edit', 'id', 'name');
            })
            ->addColumn('action', 'awards.datatables_actions')
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
                'data' => 'title',
                'title' => trans('lang.award_title'),

            ],
            [
                'data' => 'description',
                'title' => trans('lang.award_description'),

            ],
            [
                'data' => 'salon.name',
                'title' => trans('lang.award_salon_id'),

            ],
            [
                'data' => 'updated_at',
                'title' => trans('lang.award_updated_at'),
                'searchable' => false,
            ]
        ];

        $hasCustomField = in_array(Award::class, setting('custom_field_models', []));
        if ($hasCustomField) {
            $customFieldsCollection = CustomField::where('custom_field_model', Award::class)->where('in_table', '=', true)->get();
            foreach ($customFieldsCollection as $key => $field) {
                array_splice($columns, $field->order - 1, 0, [[
                    'data' => 'custom_fields.' . $field->name . '.view',
                    'title' => trans('lang.award_' . $field->name),
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
     * @param Award $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Award $model): \Illuminate\Database\Eloquent\Builder
    {
        if (auth()->user()->hasRole('salon owner')) {
            return $model->newQuery()->with("salon")->join('salon_users', 'salon_users.salon_id', '=', 'awards.salon_id')
                ->groupBy('awards.id')
                ->select('awards.*')
                ->where('salon_users.user_id', auth()->id());
        } else {
            return $model->newQuery()->with("salon")->select('awards.*');
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
        return 'awardsdatatable_' . time();
    }
}
