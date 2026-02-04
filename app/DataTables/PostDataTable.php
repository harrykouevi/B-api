<?php
/*
 * File name: PostDataTable.php
 * Last modified: 2026.01.19 at 15:53:30
 * Author:
 * Copyright (c) 2026
 */

namespace App\DataTables;

use App\Models\CustomField;
use App\Models\Post;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;

class PostDataTable extends DataTable
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
                            if (isset($column['name']) && str_contains($column['name'], '.')) {
                                $parts = explode('.', $column['name']);
                                $colName = $parts[0] . '.' . $parts[1]; 
                            } else {
                                $colName = $column['name'] ?? $column['data'];
                            }
                            $q->orWhere($colName, 'like', "%{$search}%");
                        }
                    }
                });
            }

        });

        $dataTable = $dataTable
            ->editColumn('image', function ($post) {
                return getMediaColumn($post, 'image');
            })
            ->editColumn('name', function ($post) {
                if ($post['featured']) {
                    return $post['name'] . "<span class='badge bg-" . setting('theme_color') . " p-1 m-2'>" . trans('lang.post_featured') . "</span>";
                }
                return $post['name'];
            })
            // MODIFICATION ICI : Transformation de l'ID en Lecteur Vidéo
            ->editColumn('vimeo_id', function ($post) {
                if (!empty($post->vimeo_id)) {
                    return '<iframe src="https://player.vimeo.com/video/'.$post->vimeo_id.'" 
                            width="140" height="80" frameborder="0" 
                            allow="autoplay; fullscreen" allowfullscreen></iframe>';
                }
                return '<span class="badge badge-secondary">Pas de vidéo</span>';
            })
            ->editColumn('updated_at', function ($post) {
                return getDateColumn($post, 'updated_at');
            })
            ->editColumn('salon.name', function ($post) {
                return getLinksColumnByRouteName([$post->salon], 'salons.edit', 'id', 'name');
            })
            ->addColumn('action', 'posts.datatables_actions');

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
                'data' => 'image',
                'title' => trans('lang.post_image'),
                'searchable' => false, 'orderable' => false, 'exportable' => false, 'printable' => false,
            ],
            [
                'data' => 'caption',
                'name' => 'posts.caption',
                'title' => trans('lang.post_caption'),
                'searchable' => true,
                'orderable' => true
            ],
            // AJOUT DE LA COLONNE VIDÉO DANS LE TABLEAU
            [
                'data' => 'vimeo_id',
                'title' => 'Vidéo Vimeo',
                'searchable' => false,
                'orderable' => false,
                'exportable' => false,
                'printable' => false,
            ],
            [
                'data' => 'salon.name',
                'name' => 'salon.name',
                'title' => trans('lang.post_salon_id'),
                'orderable' => true
            ],
            [
                'data' => 'updated_at',
                'title' => trans('lang.post_updated_at'),
                'searchable' => false,
                'orderable' => true
            ]
        ];

        $hasCustomField = in_array(Post::class, setting('custom_field_models', []));
        if ($hasCustomField) {
            $customFieldsCollection = CustomField::where('custom_field_model', Post::class)->where('in_table', '=', true)->get();
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
     * @param Post $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Post $model): \Illuminate\Database\Eloquent\Builder
    {
        $query = $model->newQuery()
            ->with(['salon'])
            ->select('posts.*');

        if (auth()->user()->hasRole('salon owner')) {
            $query->join('salon_users', 'salon_users.salon_id', '=', 'posts.salon_id')
                ->where('salon_users.user_id', auth()->id())
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