<?php
/*
 * File name: PaymentDataTable.php
 * Last modified: 2024.04.18 at 17:35:01
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\DataTables;

use App\Models\CustomField;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;

class PaymentDataTable extends DataTable
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

        // Gestion des filtres personnalisés (via request())
       $dataTable->filter(function ($query) {
           // if ($search = request()->get('search')['value']) {
           //     $query->where(function ($q) use ($search) {
           //         $q->whereHas('user', function ($qUser) use ($search) {
           //             $qUser->where('name', 'like', "%{$search}%");
           //         });
           //     });
           // }
           // Filtre par nom d'utilisateur
           if (request()->has('user_name') && request('user_name') !== '') {
               $query->whereHas('user', function ($q) {
                   $q->where('name', 'like', '%' . request('user_name') . '%');
               });
           }
           // Filtre par status de paiement
           if (request()->has('payment_status_id') && !is_null(request('payment_status_id'))) {
               $query->where('payment_status_id', request('payment_status_id'));
           }
           // Filtre par méthode de paiement
           if (request()->has('payment_method_id') &&  !is_null(request('payment_method_id') )) {
               $query->where('payment_method_id', request('payment_method_id'));
           }
       });

        $columns = array_column($this->getColumns(), 'data');
        $dataTable = $dataTable
            ->editColumn('updated_at', function ($payment) {
                return getDateColumn($payment, 'updated_at');
            })->editColumn('amount', function ($payment) {
                return getPriceColumn($payment, 'amount');
            })
            ->editColumn('payment_method.name', function ($payment) {
                if (isset($payment->paymentMethod))
                    return $payment->paymentMethod->name;
                else
                    return "";
            })
            ->editColumn('payment_status.status', function ($payment) {
                if (isset($payment->paymentStatus))
                    return $payment->paymentStatus->status;
                else
                    return "";
            })
            ->editColumn('user.name', function ($payment) {
                return getLinksColumnByRouteName([$payment->user], 'users.edit', 'id', 'name');
            })
            ->addColumn('action', 'payments.datatables_actions')
            ->rawColumns($columns);

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
                'data' => 'amount',
                'title' => trans('lang.payment_amount'),

            ],
            [
                'data' => 'description',
                'title' => trans('lang.payment_description'),

            ],
            (auth()->check() && auth()->user()->hasAnyRole(['admin', 'provider'])) ? [
                'data' => 'user.name',
                'title' => trans('lang.payment_user_id'),
                'name' => 'user.name', 'searchable' => true, 'orderable' => true,
            ] : null,
            [
                'data' => 'payment_method.name',
                'name' => 'paymentMethod.name',
                'title' => trans('lang.payment_payment_method_id'),

            ],
            [
                'data' => 'payment_status.status',
                'name' => 'paymentStatus.status',
                'title' => trans('lang.payment_payment_status_id'),

            ],
            [
                'data' => 'updated_at',
                'title' => trans('lang.payment_updated_at'),
                'searchable' => false,
            ]
        ];
        $columns = array_filter($columns);
        $hasCustomField = in_array(Payment::class, setting('custom_field_models', []));
        if ($hasCustomField) {
            $customFieldsCollection = CustomField::where('custom_field_model', Payment::class)->where('in_table', '=', true)->get();
            foreach ($customFieldsCollection as $key => $field) {
                array_splice($columns, $field->order - 1, 0, [[
                    'data' => 'custom_fields.' . $field->name . '.view',
                    'title' => trans('lang.payment_' . $field->name),
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
     * @param Payment $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Payment $model): \Illuminate\Database\Eloquent\Builder
    {
        if (auth()->user()->hasRole('admin')) {
            return $model->newQuery()->with("user")
                ->with("paymentMethod")
                ->with("paymentStatus")
                ->select("$model->table.*")
                ->orderBy('id', 'desc');
        } else if (auth()->user()->hasRole('salon owner')) {
            $salonId = DB::raw("json_extract(salon, '$.id')");
            return $model->newQuery()->with("user")
                ->with("paymentMethod")
                ->with("paymentStatus")
                ->join("bookings", "payments.id", "=", "bookings.payment_id")
                ->join("salon_users", "salon_users.salon_id", "=", $salonId)
                ->where('salon_users.user_id', auth()->id())
                ->groupBy('payments.id')
                ->orderBy('payments.id', 'desc')
                ->select("$model->table.*");
        } else {
            return $model->newQuery()->with("user")
                ->with("paymentMethod")
                ->with("paymentStatus")
                ->where('payments.user_id', auth()->id())
                ->select("$model->table.*")
                ->orderBy('id', 'desc');
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
            ->setTableId('payments-table')
            ->minifiedAjax()
            ->parameters(array_merge(
                config('datatables-buttons.parameters'), [
                    'language' => json_decode(
                        file_get_contents(base_path('resources/lang/' . app()->getLocale() . '/datatable.json')
                            ), true) ,
                    'searching' => false, // désactive recherche globale
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
        return 'paymentsdatatable_' . time();
    }
}
