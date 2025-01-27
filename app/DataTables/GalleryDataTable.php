<?php
/*
 * File name: GalleryDataTable.php
 * Last modified: 2024.04.18 at 17:35:01
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\DataTables;

use App\Models\CustomField;
use App\Models\Gallery;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;

class GalleryDataTable extends DataTable
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
            ->editColumn('image', function ($gallery) {
                return getMediaColumn($gallery, 'image');
            })
            ->editColumn('description', function ($gallery) {
                return getStripedHtmlColumn($gallery, 'description');
            })
            ->editColumn('salon.name', function ($gallery) {
                return getLinksColumnByRouteName([$gallery->salon], 'salons.edit', 'id', 'name');
            })
            ->editColumn('updated_at', function ($gallery) {
                return getDateColumn($gallery, 'updated_at');
            })
            ->addColumn('action', 'galleries.datatables_actions')
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
                'data' => 'description',
                'title' => trans('lang.gallery_description'),

            ],
            [
                'data' => 'image',
                'title' => trans('lang.gallery_image'),
                'searchable' => false, 'orderable' => false, 'exportable' => false, 'printable' => false,
            ],
            [
                'data' => 'salon.name',
                'name' => 'salon.name',
                'title' => trans('lang.gallery_salon_id'),
            ],
            [
                'data' => 'updated_at',
                'title' => trans('lang.gallery_updated_at'),
                'searchable' => false,
            ]
        ];

        $hasCustomField = in_array(Gallery::class, setting('custom_field_models', []));
        if ($hasCustomField) {
            $customFieldsCollection = CustomField::where('custom_field_model', Gallery::class)->where('in_table', '=', true)->get();
            foreach ($customFieldsCollection as $key => $field) {
                array_splice($columns, $field->order - 1, 0, [[
                    'data' => 'custom_fields.' . $field->name . '.view',
                    'title' => trans('lang.gallery_' . $field->name),
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
     * @param Gallery $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Gallery $model): \Illuminate\Database\Eloquent\Builder
    {
        if (auth()->user()->hasRole('salon owner')) {
            return $model->newQuery()->with("salon")
                ->join('salon_users', 'salon_users.salon_id', '=', 'galleries.salon_id')
                ->groupBy('galleries.id')
                ->select("$model->table.*")
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
        return 'galleriesdatatable_' . time();
    }
}
