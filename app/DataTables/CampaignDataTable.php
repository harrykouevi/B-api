<?php
/*
 * File name: CampaignDataTable.php
 * Last modified: 2025.03.09
 * Author: CHARM Platform
 */

namespace App\DataTables;

use App\Models\Campaign;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Support\Str;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;

class CampaignDataTable extends DataTable
{
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
            if (request()->filled('audience')) {
                $query->where('audience', request('audience'));
            }
            if (request()->filled('created_from')) {
                $query->whereDate('created_at', '>=', request('created_from'));
            }
            if (request()->filled('created_to')) {
                $query->whereDate('created_at', '<=', request('created_to'));
            }
        });

        $columns = array_column($this->getColumns(), 'data');

        return $dataTable
            ->editColumn('message', function ($campaign) {
                return Str::limit($campaign->message, 80);
            })
            ->editColumn('audience', function ($campaign) {
                $key = 'lang.campaign_audience_' . $campaign->audience;
                $translated = trans($key);
                return $translated !== $key ? $translated : $campaign->audience;
            })
            ->editColumn('sent_via', function ($campaign) {
                $key = 'lang.campaign_sent_via_' . $campaign->sent_via;
                $translated = trans($key);
                return $translated !== $key ? $translated : $campaign->sent_via;
            })
            ->editColumn('status', function ($campaign) {
                $key = 'lang.campaign_status_' . $campaign->status;
                $translated = trans($key);
                $label = $translated !== $key ? $translated : $campaign->status;
                if ($campaign->status === 'success') {
                    $class = 'badge badge-success';
                } elseif ($campaign->status === 'partial') {
                    $class = 'badge badge-warning';
                } else {
                    $class = 'badge badge-danger';
                }
                return '<span class="' . $class . '">' . $label . '</span>';
            })
            ->editColumn('creator.name', function ($campaign) {
                if (!isset($campaign->creator)) {
                    return '';
                }
                return getLinksColumnByRouteName([$campaign->creator], 'users.edit', 'id', 'name');
            })
            ->editColumn('created_at', function ($campaign) {
                return getDateColumn($campaign, 'created_at');
            })
            ->rawColumns($columns);
    }

    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns(): array
    {
        return [
            [
                'data' => 'title',
                'title' => trans('lang.campaign_title'),
            ],
            [
                'data' => 'message',
                'title' => trans('lang.campaign_message'),
            ],
            [
                'data' => 'audience',
                'title' => trans('lang.campaign_audience'),
            ],
            [
                'data' => 'sent_via',
                'title' => trans('lang.campaign_sent_via'),
            ],
            [
                'data' => 'sent_count',
                'title' => trans('lang.campaign_sent_count'),
                'searchable' => false,
            ],
            [
                'data' => 'failed_count',
                'title' => trans('lang.campaign_failed_count'),
                'searchable' => false,
            ],
            [
                'data' => 'status',
                'title' => trans('lang.campaign_status'),
                'searchable' => false,
            ],
            [
                'data' => 'creator.name',
                'title' => trans('lang.campaign_created_by'),
                'searchable' => false,
                'orderable' => false,
            ],
            [
                'data' => 'created_at',
                'title' => trans('lang.campaign_created_at'),
                'searchable' => false,
            ],
        ];
    }

    /**
     * Get query source of dataTable.
     *
     * @param Campaign $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Campaign $model): \Illuminate\Database\Eloquent\Builder
    {
        return $model->newQuery()
            ->with('creator')
            ->select("$model->table.*")
            ->orderBy('id', 'desc');
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
            ->setTableId('campaigns-table')
            ->minifiedAjax()
            ->parameters(array_merge(
                config('datatables-buttons.parameters'),
                [
                    'language' => json_decode(
                        file_get_contents(base_path('resources/lang/' . app()->getLocale() . '/datatable.json')),
                        true
                    ),
                    'searching' => false,
                    'processing' => true,
                    'serverSide' => true,
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
        return 'campaignsdatatable_' . time();
    }
}
