<?php
/*
 * File content: CommentDataTable.php
 * Last modified: 2026.02.19 at 13:53:30
 * Author: harrykouevi - https://github.com/harrykouevi
 * Copyright (c) 2026
 */

namespace App\DataTables;

use App\Models\CustomField;
use App\Models\Comment;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;

class CommentDataTable extends DataTable
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
            
            if (request()->has('search') && (!is_null(request('search')['value']) || request('search')['value'] != '')) {
                $search = request('search')['value'] ;
                $columns = $this->getColumns();
                $query->where(function ($q) use ($columns, $search) {
                    foreach ($columns as $column) {
                        if ($column['searchable'] ?? false) {
                            if (isset($column['content']) && str_contains($column['content'], '.')) {
                                $parts = explode('.', $column['content']);
                                $colName = $parts[0] . '.' . $parts[1]; 
                            } else {
                                $colName = $column['content'] ?? $column['data'];
                            }
                            $q->orWhere($colName, 'like', "%{$search}%");
                        }
                    }
                });
            }

        });


        $dataTable = $dataTable
           
            ->editColumn('content', function ($comment) {
               
                return $comment['content'];
            })
           
           
            ->editColumn('updated_at', function ($comment) {
                return getDateColumn($comment, 'updated_at');
            })
            ->editColumn('user.name', function ($comment) {
                return getLinksColumnByRouteName([$comment->user], 'users.edit', 'id', 'name');
            })
            ->editColumn('post', function ($comment) {
                return getLinksColumnByRouteName([$comment->post], 'posts.show', 'uuid', 'caption');
            })
              ->editColumn('report_count', function ($comment) {
                
                return  '<span class="badge badge-secondary">'.$comment->report_count.'</span>';

            })
           
            ->addColumn('action', 'comments.datatables_actions');

        // On récupère toutes les colonnes pour autoriser le rendu HTML
        $columns = array_column($this->getColumns(), 'data');
        $dataTable = $dataTable->rawColumns(array_merge($columns, ['action', 'vimeo_id']));

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
                'data' => 'content',
                'title' => trans('lang.comment_content'),
                'searchable' => true,
                'orderable' => true
            ],
            [
                'data' => 'user.name',
                'title' => trans('lang.user_id'),
            ],

            [
                'data' => 'post',
                'title' => trans('lang.post_id'),
            ],
           
           [
                'data' => 'report_count',
                'title' => trans('lang.report_count'),
                'orderable' => true

            ],
         
            [
                'data' => 'updated_at',
                'title' => trans('lang.comment_updated_at'),
                'searchable' => false,
                'orderable' => true
            ]
        ];

        $hasCustomField = in_array(Comment::class, setting('custom_field_models', []));
        if ($hasCustomField) {
            $customFieldsCollection = CustomField::where('custom_field_model', Comment::class)->where('in_table', '=', true)->get();
            foreach ($customFieldsCollection as $key => $field) {
                array_splice($columns, $field->order - 1, 0, [[
                    'data' => 'custom_fields.' . $field->name . '.view',
                    'title' => trans('lang.post_' . $field->name),
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
     * @param Comment $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Comment $model): \Illuminate\Database\Eloquent\Builder
    {
        $query = $model->newQuery()
            ->with(["user"])
            ->select('comments.*')
            ->withCount('reports') // 👈 fonctionne aussi en morph
            ->whereHas('reports')
            ->orderByDesc('reports_count');
        if (auth()->user()->hasRole('salon owner')) {
            $query->join('posts',  'comments.post_id', '=', 'posts.id')
                ->where('posts.user_id', auth()->id())
                ->groupBy('posts.id');
        }

        return $query;
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
        return 'postsdatatable_' . time();
    }
}