<?php
/*
 * File name: PostDataTable.php
 * Last modified: 2026.02.23
 * Author: Gemini
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
use Illuminate\Support\Facades\Gate;

class PostDataTable extends DataTable
{
    public static array $customFields = [];

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
            ->editColumn('media', function ($post) {

                if($post->has_media == true){ 
                    $media = $post->getFirstMedia('*') ;
                    if(str_starts_with($media->mime_type, 'application/') || str_starts_with($media->mime_type, 'video/')) {
                        
                            return '<iframe src="'.$post->getFirstMediaUrl('*').'" 
                                    width="140" height="80" frameborder="0" 
                                    allow="autoplay; fullscreen" allowfullscreen></iframe>';
                    }
                }
               
                return getMediaColumn($post, '*');
            })
            ->editColumn('name', function ($post) {
                if ($post['featured']) {
                    return $post['name'] . "<span class='badge bg-" . setting('theme_color') . " p-1 m-2'>" . trans('lang.post_featured') . "</span>";
                }
                return $post['name'];
            })
            ->editColumn('view_count', function ($post) {
                return '<span class="badge badge-secondary">'.$post->view_count.'</span>';
            })
            ->editColumn('like_count', function ($post) {
                return '<span class="badge badge-secondary">'.$post->like_count.'</span>';
            })
            /** MODIFICATION ICI : Nombre de commentaires cliquable **/
            ->editColumn('comment_count', function ($post) {
                return '<a href="'.route('comments.index', ['post_id' => $post->id]).'" class="badge badge-info" data-toggle="tooltip" title="Cliquez pour modérer">
                            <i class="fas fa-comments mr-1"></i>'.$post->comment_count.'
                        </a>';
            })
            ->editColumn('favory_count', function ($post) {
                
                return  '<span class="badge badge-secondary">'.$post->favory_count.'</span>';

            })
            ->editColumn('updated_at', function ($post) {
                return getDateColumn($post, 'updated_at');
            })
            ->editColumn('user.name', function ($post) {
                return getLinksColumnByRouteName([$post->author], 'users.edit', 'id', 'name');
            })
            ->editColumn('salon.name', function ($post) {
                return getLinksColumnByRouteName([$post->salon], 'salons.edit', 'id', 'name');
            })
            // ->addColumn('action', 'posts.datatables_actions');
            ->addColumn('action', function ($row) {
                $html = '<div class="btn-group btn-group-sm">';

                // Vérifie la permission
                if (Gate::allows('posts.show')) {
                    $html .= '<a data-toggle="tooltip" data-placement="left" href="'.route('posts.show', $row->uuid).'" class="btn btn-link">
                                <i class="fas fa-eye"></i>
                            </a>';
                }

                if (Gate::allows('posts.destroy')) { 
                    $html .= '<form action="'.route('posts.destroy', $row->uuid).'"
                        method="POST"
                        style="display:inline-block;">';

                    $html .= csrf_field();
                    $html .= method_field('DELETE');

                    $html .= '<button type="submit"
                                    class="btn btn-link text-danger"
                                    onclick="return confirm(\'Are you sure?\')">
                                    <i class="fas fa-trash"></i>
                            </button>';

                    $html .= '</form>';  
                   
                }
                $html .= '</div>';

                return $html;
            })
             ;

        // // On récupère toutes les colonnes pour autoriser le rendu HTML
        $columns = array_column($this->getColumns(), 'data');
        
        /** MODIFICATION ICI : Ajout de comment_count dans rawColumns pour rendre le lien actif **/
        $dataTable = $dataTable->rawColumns(array_merge($columns, ['action', 'vimeo_id', 'comment_count']));

        return $dataTable;
    }

    protected function getColumns(): array
    {
        $columns = [
            [
                'data' => 'media',
                'title' => trans('lang.post_media'),
                'searchable' => false, 'orderable' => false, 'exportable' => false, 'printable' => false,
            ],
            [
                'data' => 'caption',
                'name' => 'posts.caption',
                'title' => trans('lang.post_caption'),
                'searchable' => true,
                'orderable' => true
            ],
            [
                'data' => 'user.name',
                'title' => trans('lang.user_id'),
            ],
            [
                'data' => 'salon.name',
                'title' => trans('lang.post_salon_id'),
                'searchable' => true,
                'orderable' => true
            ],
            [
                'data' => 'view_count',
                'title' => trans('lang.view_count'),
                'orderable' => true
            ],
            [
                'data' => 'like_count',
                'title' => trans('lang.like_count'),
                'orderable' => true
            ],
            [
                'data' => 'comment_count',
                'title' => trans('lang.comment_count'),
                'orderable' => true
            ],
             [
                'data' => 'favory_count',
                'title' => trans('lang.favory_count'),
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

    public function query(Post $model): \Illuminate\Database\Eloquent\Builder
    {
        $query = $model->newQuery()
            ->with(['salon',"user"])
            ->select('posts.*');

        if (auth()->user()->hasRole('salon owner')) {
            $query->join('salon_users', 'salon_users.salon_id', '=', 'posts.salon_id')
                ->where('salon_users.user_id', auth()->id())
                ->groupBy('posts.id');
        }

        return $query;
    }

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

    public function pdf(): mixed
    {
        $data = $this->getDataForPrint();
        $pdf = PDF::loadView($this->printPreview, compact('data'));
        return $pdf->download($this->filename() . '.pdf');
    }

    protected function filename(): string
    {
        return 'postsdatatable_' . time();
    }
}