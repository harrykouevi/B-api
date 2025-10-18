<?php
/*
 * File name: CategoryDataTable.php
 * Last modified: 2024.04.18 at 17:35:01
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\DataTables;

use App\Models\Category;
use App\Models\CustomField;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use JsonException;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;

class CategoryDataTable extends DataTable
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
            ->editColumn('image', function ($category) {
                return getMediaColumn($category, 'image', '', '');
            })
            ->editColumn('description', function ($category) {
                return getStripedHtmlColumn($category, 'description');
            })
            ->editColumn('name', function ($category) {
                return $category->name;
            })
            ->editColumn('color', function ($category) {
                return getColorColumn($category, 'color');
            })
            ->editColumn('featured', function ($category) {
                return getBooleanColumn($category, 'featured');
            })
            ->editColumn('parent_category.name', function ($category) {
                return getLinksColumnByRouteName([$category->parentCategory], 'categories.edit', 'id', 'name');
            })
            ->editColumn('updated_at', function ($category) {
                return getDateColumn($category, 'updated_at');
            })
            ->addColumn('action', 'categories.datatables_actions')
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
                'title' => trans('lang.category_image'),
                'searchable' => false, 'orderable' => false, 'exportable' => false, 'printable' => false,
            ],
            [
                'data' => 'name',
                'title' => trans('lang.category_name'),

            ],
            [
                'data' => 'color',
                'title' => trans('lang.category_color'),

            ],
            [
                'data' => 'description',
                'title' => trans('lang.category_description'),

            ],
            [
                'data' => 'featured',
                'title' => trans('lang.category_featured'),
            ],
            [
                'data' => 'order',
                'title' => trans('lang.category_order'),
            ],
            [
                'data' => 'parent_category.name',
                'name' => 'parentCategory.name',
                'title' => trans('lang.category_parent_id'),
                'searchable' => false, 'orderable' => false,
            ],
            [
                'data' => 'updated_at',
                'title' => trans('lang.category_updated_at'),
                'searchable' => false,
            ]
        ];

        $hasCustomField = in_array(Category::class, setting('custom_field_models', []), true);
        if ($hasCustomField) {
            $customFieldsCollection = CustomField::where('custom_field_model', Category::class)->where('in_table', '=', true)->get();
            foreach ($customFieldsCollection as $key => $field) {
                array_splice($columns, $field->order - 1, 0, [[
                    'data' => 'custom_fields.' . $field->name . '.view',
                    'title' => trans('lang.category_' . $field->name),
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
     * @param Category $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Category $model): \Illuminate\Database\Eloquent\Builder
    {
        return $model->newQuery()->with("parent")->select("categories.*");
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return Builder
     * @throws JsonException
     */
    public function html(): Builder
    {
        return $this->builder()
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->addAction(['width' => '80px', 'printable' => false, 'responsivePriority' => '100'])
            ->parameters(array_merge(
                config('datatables-buttons.parameters'), [
                    'language' => json_decode(file_get_contents(base_path('resources/lang/' . app()->getLocale() . '/datatable.json')
                    ), true, 512, JSON_THROW_ON_ERROR)
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
        return PDF::loadView($this->printPreview, compact('data'))->download($this->filename() . '.pdf');
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename(): string
    {
        return 'categoriesdatatable_' . time();
    }
}
