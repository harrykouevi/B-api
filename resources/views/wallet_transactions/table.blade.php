@push('css_lib')
    @include('layouts.datatables_css')
@endpush
{{-- 🔍 Formulaire de filtres personnalisés --}}
<form id="filter-form" class="mb-4">
    <div class="row g-2 align-items-end">
       

        <div class="col-md-3">
            <label for="action_search" class="form-label">Statut de paiement</label>
            <select id="action_search" class="form-control-sm form-control select2 ">
                <option value="">Tous</option>
                <option value="debit">{{ __("lang.wallet_transaction_debit") }}</option>
                <option value="credit">{{ __("lang.wallet_transaction_credit") }}</option>

            </select>
        </div>

        

       

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
            // // 🧠 On récupère l'instance générée par Laravel DataTables
            // let table = window.LaravelDataTables["dataTableBuilder"];
            let table = $('#wallet-transactions-table').DataTable() ;
            // let table = window.LaravelDataTables["dataTableBuilder"];

            // 🔁 Ajout des filtres personnalisés à l’appel AJAX
            table.on('preXhr.dt', function (e, settings, data) {
                data.action_search = $('#action_search').val();
            });

            // 📤 Rechargement avec les filtres
            $('#filter-form').on('submit', function (e) {
                e.preventDefault();
                table.draw();
            });

            // 🔄 Réinitialisation des filtres
            $('#reset-filters').on('click', function () {
                $('#filter-form')[0].reset();
                table.draw();
            });
        });
    </script>
@endpush
