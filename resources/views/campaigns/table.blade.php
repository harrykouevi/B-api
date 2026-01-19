@push('css_lib')
    @include('layouts.datatables_css')
@endpush

{{-- 🔍 Formulaire de filtres personnalisés --}}
<form id="filter-form" class="mb-4">
    <div class="row g-2 align-items-end">
        <div class="col-md-4">
            <label for="audience" class="form-label">{{ trans('lang.campaign_audience') }}</label>
            <select id="audience" class="form-control-sm form-control">
                <option value="">{{ trans('lang.campaign_filter_all') }}</option>
                <option value="all">{{ trans('lang.campaign_audience_all') }}</option>
                <option value="salon">{{ trans('lang.campaign_audience_salon') }}</option>
                <option value="client">{{ trans('lang.campaign_audience_client') }}</option>
            </select>
        </div>
        <div class="col-md-4">
            <label for="created_from" class="form-label">{{ trans('lang.campaign_filter_from') }}</label>
            <input type="date" id="created_from" class="form-control-sm form-control">
        </div>
        <div class="col-md-4">
            <label for="created_to" class="form-label">{{ trans('lang.campaign_filter_to') }}</label>
            <input type="date" id="created_to" class="form-control-sm form-control">
        </div>
    </div>
    <div class="row g-2 align-items-end mt-2">
        <div class="col-md-2 d-grid">
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
            let table = $('#campaigns-table').DataTable();

            table.on('preXhr.dt', function (e, settings, data) {
                data.audience = $('#audience').val();
                data.created_from = $('#created_from').val();
                data.created_to = $('#created_to').val();
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
