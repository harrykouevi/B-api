@push('css_lib')
    @include('layouts.datatables_css')
@endpush

{{-- 🔍 Formulaire de filtres personnalisés --}}
<form id="filter-form" class="mb-4">
    <div class="row g-2 align-items-end">
        <div class="col-md-4">
            <label for="user_name" class="form-label">Nom de l'utilisateur</label>
            <input type="text" id="user_name" class="form-control-sm  form-control" placeholder="Nom">
        </div>

        <div class="col-md-3">
            <label for="payment_status_id" class="form-label">Statut de paiement</label>
            <select id="payment_status_id" class="form-control-sm form-control select2 ">
                <option value="">Tous</option>
                @foreach ($paymentStatuses as $status)
                    <option value="{{ $status->id }}">{{ $status->status }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3">
            <label for="payment_method_id" class="form-label">Méthode de paiement</label>
            <select id="payment_method_id" class="form-control-sm form-control select2 ">
                <option value="">Toutes</option>
                @foreach ($paymentMethods as $method)
                    <option value="{{ $method->id }}">{{ $method->name }}</option>
                @endforeach
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
            let table = $('#payments-table').DataTable() ;
            // let table = window.LaravelDataTables["dataTableBuilder"];

            // 🔁 Ajout des filtres personnalisés à l’appel AJAX
            table.on('preXhr.dt', function (e, settings, data) {
                data.user_name = $('#user_name').val();
                data.payment_status_id = $('#payment_status_id').val();
                data.payment_method_id = $('#payment_method_id').val();
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
