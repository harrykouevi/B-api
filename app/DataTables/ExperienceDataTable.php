<?php
/*
 * File name: ExperienceDataTable.php
 * Last modified: 2024.04.18 at 17:35:01
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\DataTables;

use App\Models\CustomField;
use App\Models\Experience;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;

class ExperienceDataTable extends DataTable
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
            ->editColumn('updated_at', function ($experience) {
                return getDateColumn($experience, 'updated_at');
            })
            ->editColumn('title', function ($experience) {
                return $experience->title;
            })
            ->editColumn('description', function ($experience) {
                return getStripedHtmlColumn($experience, 'description');
            })
            ->editColumn('salon.name', function ($experience) {
                return getLinksColumnByRouteName([$experience->salon], 'salons.edit', 'id', 'name');
            })
            ->addColumn('action', 'experiences.datatables_actions')
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
                'title' => trans('lang.experience_title'),

            ],
            [
                'data' => 'description',
                'title' => trans('lang.experience_description'),

            ],
            [
                'data' => 'salon.name',
                'title' => trans('lang.experience_salon_id'),

            ],
            [
                'data' => 'updated_at',
                'title' => trans('lang.experience_updated_at'),
                'searchable' => false,
            ]
        ];

        $hasCustomField = in_array(Experience::class, setting('custom_field_models', []));
        if ($hasCustomField) {
            $customFieldsCollection = CustomField::where('custom_field_model', Experience::class)->where('in_table', '=', true)->get();
            foreach ($customFieldsCollection as $key => $field) {
                array_splice($columns, $field->order - 1, 0, [[
                    'data' => 'custom_fields.' . $field->name . '.view',
                    'title' => trans('lang.experience_' . $field->name),
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
     * @param Experience $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Experience $model): \Illuminate\Database\Eloquent\Builder
    {
        if (auth()->user()->hasRole('salon owner')) {
            return $model->newQuery()->with("salon")->join('salon_users', 'salon_users.salon_id', '=', 'experiences.salon_id')
                ->groupBy('experiences.id')
                ->select('experiences.*')
                ->where('salon_users.user_id', auth()->id());
        } else {
            return $model->newQuery()->with("salon")->select("$model->table.*");
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
        return 'experiencesdatatable_' . time();
    }
}
