<?php
/*
 * File name: PlatformRevenueDataTable.php
 * Last modified: 2025.03.09
 * Author: CHARM Platform
 */

namespace App\DataTables;

use App\Models\PlatformRevenue;
use App\Types\PlatformRevenueType;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;

class PlatformRevenueDataTable extends DataTable
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
            $this->applyFilters($query);
        });

        $totals = $this->calculateTotals($query);
        $columns = array_column($this->getColumns(), 'data');

        return $dataTable
            ->editColumn('type', function ($revenue) {
                $key = 'lang.platform_revenue_type_' . $revenue->type;
                $translated = trans($key);
                return $translated !== $key ? $translated : $revenue->type;
            })
            ->editColumn('amount', function ($revenue) {
                $amountHtml = getPriceColumn($revenue, 'amount');
                if ($revenue->amount < 0) {
                    return '<span class="text-danger">' . $amountHtml . '</span>';
                }
                return '<span class="text-success">' . $amountHtml . '</span>';
            })
            ->editColumn('booking.id', function ($revenue) {
                if (!isset($revenue->booking)) {
                    return '';
                }
                return getLinksColumnByRouteName([$revenue->booking], 'bookings.show', 'id', 'id');
            })
            ->editColumn('salon.name', function ($revenue) {
                if (!isset($revenue->salon)) {
                    return '';
                }
                return getLinksColumnByRouteName([$revenue->salon], 'salons.edit', 'id', 'name');
            })
            ->editColumn('customer.name', function ($revenue) {
                if (!isset($revenue->customer)) {
                    return '';
                }
                return getLinksColumnByRouteName([$revenue->customer], 'users.edit', 'id', 'name');
            })
            ->editColumn('created_at', function ($revenue) {
                return getDateColumn($revenue, 'created_at');
            })
            ->with([
                'total_commissions' => getPrice($totals['total_commissions']),
                'total_losses' => getPrice($totals['total_losses']),
            ])
            ->rawColumns($columns);
    }

    private function applyFilters($query): void
    {
        $type = request('type');
        if ($type !== null && $type !== '') {
            $query->where('type', $type);
        }

        $amountMin = request('amount_min');
        if ($amountMin !== null && $amountMin !== '') {
            $query->where('amount', '>=', $amountMin);
        }

        $amountMax = request('amount_max');
        if ($amountMax !== null && $amountMax !== '') {
            $query->where('amount', '<=', $amountMax);
        }

        $bookingId = request('booking_id');
        if ($bookingId !== null && $bookingId !== '') {
            $query->where('booking_id', $bookingId);
        }

        $salonId = request('salon_id');
        if ($salonId !== null && $salonId !== '') {
            $query->where('salon_id', $salonId);
        }

        $customerId = request('customer_id');
        if ($customerId !== null && $customerId !== '') {
            $query->where('customer_id', $customerId);
        }

        $description = request('description');
        if ($description !== null && $description !== '') {
            $query->where('description', 'like', '%' . $description . '%');
        }

        $createdFrom = request('created_from');
        if ($createdFrom !== null && $createdFrom !== '') {
            $query->whereDate('created_at', '>=', $createdFrom);
        }

        $createdTo = request('created_to');
        if ($createdTo !== null && $createdTo !== '') {
            $query->whereDate('created_at', '<=', $createdTo);
        }
    }

    private function calculateTotals($query): array
    {
        $totalsQuery = clone $query;
        $this->applyFilters($totalsQuery);

        $totalCommissions = (clone $totalsQuery)
            ->where('type', PlatformRevenueType::COMMISSION->value)
            ->sum('amount');

        $totalLosses = (clone $totalsQuery)
            ->where('amount', '<', 0)
            ->sum('amount');
        $totalLosses = abs($totalLosses);

        return [
            'total_commissions' => (float) $totalCommissions,
            'total_losses' => (float) $totalLosses,
        ];
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
                'data' => 'type',
                'title' => trans('lang.platform_revenue_type'),
            ],
            [
                'data' => 'amount',
                'title' => trans('lang.platform_revenue_amount'),
            ],
            [
                'data' => 'booking.id',
                'title' => trans('lang.platform_revenue_booking_id'),
                'orderable' => false,
                'searchable' => false,
            ],
            [
                'data' => 'salon.name',
                'title' => trans('lang.platform_revenue_salon_id'),
                'orderable' => false,
                'searchable' => false,
            ],
            [
                'data' => 'customer.name',
                'title' => trans('lang.platform_revenue_customer_id'),
                'orderable' => false,
                'searchable' => false,
            ],
            [
                'data' => 'description',
                'title' => trans('lang.platform_revenue_description'),
            ],
            [
                'data' => 'created_at',
                'title' => trans('lang.platform_revenue_created_at'),
                'searchable' => false,
            ],
        ];
    }

    /**
     * Get query source of dataTable.
     *
     * @param PlatformRevenue $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(PlatformRevenue $model): \Illuminate\Database\Eloquent\Builder
    {
        return $model->newQuery()
            ->with(['booking', 'salon', 'customer'])
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
            ->setTableId('platform-revenues-table')
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
        return 'platform_revenuesdatatable_' . time();
    }
}
