@push('css_lib')
    @include('layouts.datatables_css')
@endpush

<div class="row mb-3">
    <div class="col-md-4">
        <div class="small-box bg-success">
            <div class="inner">
                <h4 id="total-commissions">{!! getPrice(0) !!}</h4>
                <p>{{ trans('lang.platform_revenue_total_commissions') }}</p>
            </div>
            <div class="icon">
                <i class="fas fa-percentage"></i>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="small-box bg-danger">
            <div class="inner">
                <h4 id="total-losses">{!! getPrice(0) !!}</h4>
                <p>{{ trans('lang.platform_revenue_total_losses') }}</p>
            </div>
            <div class="icon">
                <i class="fas fa-arrow-down"></i>
            </div>
        </div>
    </div>
</div>

{{-- 🔍 Formulaire de filtres personnalisés --}}
<form id="filter-form" class="mb-4">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label for="type" class="form-label">{{ trans('lang.platform_revenue_type') }}</label>
            <select id="type" class="form-control-sm form-control select2">
                <option value="">Tous</option>
                @foreach ($types as $type)
                    <option value="{{ $type->value }}">{{ trans('lang.platform_revenue_type_' . $type->value) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label for="booking_id" class="form-label">{{ trans('lang.platform_revenue_booking_id') }}</label>
            <input type="number" id="booking_id" class="form-control-sm form-control" placeholder="ID booking">
        </div>
        <div class="col-md-3">
            <label for="salon_id" class="form-label">{{ trans('lang.platform_revenue_salon_id') }}</label>
            <input type="number" id="salon_id" class="form-control-sm form-control" placeholder="ID salon">
        </div>
        <div class="col-md-3">
            <label for="customer_id" class="form-label">{{ trans('lang.platform_revenue_customer_id') }}</label>
            <input type="number" id="customer_id" class="form-control-sm form-control" placeholder="ID client">
        </div>
    </div>
    <div class="row g-2 align-items-end mt-1">
        <div class="col-md-3">
            <label for="amount_min" class="form-label">Montant min</label>
            <input type="number" step="0.01" id="amount_min" class="form-control-sm form-control" placeholder="0">
        </div>
        <div class="col-md-3">
            <label for="amount_max" class="form-label">Montant max</label>
            <input type="number" step="0.01" id="amount_max" class="form-control-sm form-control" placeholder="0">
        </div>
        <div class="col-md-3">
            <label for="created_from" class="form-label">Date début</label>
            <input type="date" id="created_from" class="form-control-sm form-control">
        </div>
        <div class="col-md-3">
            <label for="created_to" class="form-label">Date fin</label>
            <input type="date" id="created_to" class="form-control-sm form-control">
        </div>
    </div>
    <div class="row g-2 align-items-end mt-1">
        <div class="col-md-9">
            <label for="description" class="form-label">{{ trans('lang.platform_revenue_description') }}</label>
            <input type="text" id="description" class="form-control-sm form-control" placeholder="Description">
        </div>
        <div class="col-md-3 d-grid">
            <button type="submit" class="btn btn-sm btn-primary">Filtrer</button>
            <button type="button" id="reset-filters" class="btn btn-sm btn-secondary mt-1">Réinitialiser</button>
        </div>
    </div>
</form>

{!! $dataTable->table(['width' => '100%']) !!}

@push('scripts_lib')
    @include('layouts.datatables_js')
    {!! $dataTable->scripts() !!}
    <script>
        $(document).ready(function () {
            let table = $('#platform-revenues-table').DataTable();

            table.on('preXhr.dt', function (e, settings, data) {
                data.type = $('#type').val();
                data.booking_id = $('#booking_id').val();
                data.salon_id = $('#salon_id').val();
                data.customer_id = $('#customer_id').val();
                data.amount_min = $('#amount_min').val();
                data.amount_max = $('#amount_max').val();
                data.created_from = $('#created_from').val();
                data.created_to = $('#created_to').val();
                data.description = $('#description').val();
            });

            table.on('xhr.dt', function (e, settings, json) {
                if (json) {
                    $('#total-commissions').html(json.total_commissions || '-');
                    $('#total-losses').html(json.total_losses || '-');
                }
            });

            $('#filter-form').on('submit', function (e) {
                e.preventDefault();
                table.draw();
            });

            $('#reset-filters').on('click', function () {
                $('#filter-form')[0].reset();
                table.draw();
            });
        });
    </script>
@endpush
