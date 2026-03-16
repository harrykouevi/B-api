<?php
/*
 * File name: StoryDataTable.php
 * Last modified: 2026.02.24 at 11:53:30
 * Author:
 * Copyright (c) 2026
 */

namespace App\DataTables;

use App\Models\CustomField;
use App\Models\Story;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;
use Illuminate\Support\Facades\Gate;

class StoryDataTable extends DataTable
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
            ->editColumn('media', function ($story) {

                if($story->has_media == true){ 
                    $media = $story->getFirstMedia('*') ;
                    if(str_starts_with($media->mime_type, 'application/') || str_starts_with($media->mime_type, 'video/')) {
                        
                            return '<iframe src="'.$story->getFirstMediaUrl('*').'" 
                                    width="140" height="80" frameborder="0" 
                                    allow="autoplay; fullscreen" allowfullscreen></iframe>';
                    }
                }
               
                return getMediaColumn($story, '*');
            })
            ->editColumn('name', function ($story) {
                if ($story['featured']) {
                    return $story['name'] . "<span class='badge bg-" . setting('theme_color') . " p-1 m-2'>" . trans('lang.story_featured') . "</span>";
                }
                return $story['name'];
            })
           
            // ->editColumn('view_count', function ($story) {
                
            //     return  '<span class="badge badge-secondary">'.$story->view_count.'</span>';
            // })

            // ->editColumn('like_count', function ($story) {
                
            //     return  '<span class="badge badge-secondary">'.$story->like_count.'</span>';

            // })
            // ->editColumn('comment_count', function ($story) {
                
            //     return  '<span class="badge badge-secondary">'.$story->comment_count.'</span>';

            // })
            // ->editColumn('favory_count', function ($story) {
                
            //     return  '<span class="badge badge-secondary">'.$story->favory_count.'</span>';

            // })
            ->editColumn('expires_at', function ($story) {
                if ($story['is_expired']) {
                    return "<p><span class='badge bg-warning p-1 m-2'>1</span> ". getDateColumn($story, 'expires_at') ."<p>";
                }
                
                
                return "<p><span class='badge bg-success p-1 m-2'>0</span>  expire " . getDateColumn($story, 'expires_at') ."<p>";

            })
            ->editColumn('updated_at', function ($story) {
                return getDateColumn($story, 'updated_at');
            })
            ->editColumn('user.name', function ($story) {
                return getLinksColumnByRouteName([$story->user], 'users.edit', 'id', 'name');
            })
            // ->editColumn('salon.name', function ($story) {
            //     return getLinksColumnByRouteName([$story->salon], 'salons.edit', 'id', 'name');
            // })
            // ->addColumn('action', 'stories.datatables_actions');
            ->addColumn('action', function ($row) {
                $html = '<div class="btn-group btn-group-sm">';

                // Vérifie la permission
                if (Gate::allows('stories.show')) {
                    $html .= '<a data-toggle="tooltip" data-placement="left" href="'.route('stories.show', $row->uuid).'" class="btn btn-link">
                                <i class="fas fa-eye"></i>
                            </a>';
                }

                if (Gate::allows('stories.destroy')) { 
                    $html .= '<form action="'.route('stories.destroy', $row->uuid).'"
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
                'data' => 'media',
                'title' => trans('lang.story_media'),
                'searchable' => false, 'orderable' => false, 'exportable' => false, 'printable' => false,
            ],
            // [
            //     'data' => 'caption',
            //     'name' => 'stories.caption',
            //     'title' => trans('lang.story_caption'),
            //     'searchable' => true,
            //     'orderable' => true
            // ],
            // AJOUT DE LA COLONNE VIDÉO DANS LE TABLEAU
            // [
            //     'data' => 'vimeo_id',
            //     'title' => 'Vidéo Vimeo',
            //     'searchable' => false,
            //     'orderable' => false,
            //     'exportable' => false,
            //     'printable' => false,
            // ],
            [
                'data' => 'user.name',
                'title' => trans('lang.user_id'),
            ],
           
            // [
            //     'data' => 'salon.name',
            //     'title' => trans('lang.story_salon_id'),
            //     'searchable' => true,
            //     'orderable' => true
            // ],
            //  [
            //     'data' => 'view_count',
            //     'title' => trans('lang.view_count'),
            //     'orderable' => true

            // ],
            //  [
            //     'data' => 'like_count',
            //     'title' => trans('lang.like_count'),
            //     'orderable' => true

            // ],
            // [
            //     'data' => 'comment_count',
            //     'title' => trans('lang.comment_count'),
            //     'orderable' => true

            // ],
            //  [
            //     'data' => 'favory_count',
            //     'title' => trans('lang.favory_count'),
            //     'orderable' => true

            // ],
             [
                'data' => 'expires_at',
                'title' => trans('lang.story_expires_at'),
                'searchable' => false,
                'orderable' => true
            ],
            [
                'data' => 'updated_at',
                'title' => trans('lang.story_updated_at'),
                'searchable' => false,
                'orderable' => true
            ]
        ];

        $hasCustomField = in_array(Story::class, setting('custom_field_models', []));
        if ($hasCustomField) {
            $customFieldsCollection = CustomField::where('custom_field_model', Story::class)->where('in_table', '=', true)->get();
            foreach ($customFieldsCollection as $key => $field) {
                array_splice($columns, $field->order - 1, 0, [[
                    'data' => 'custom_fields.' . $field->name . '.view',
                    'title' => trans('lang.story_' . $field->name),
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
     * @param Story $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Story $model): \Illuminate\Database\Eloquent\Builder
    {
        $query = $model->newQuery()
            ->with(["user"])
            ->select('stories.*');

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