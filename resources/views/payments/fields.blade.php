<div class="container my-4">
    <div class="card shadow-sm rounded">


        <div class="card-body">
            <div class="row">
                <!-- Colonne gauche -->
                <div class="col-md-6">
                    <dl class="row">
                        <dt class="col-sm-5">{{ trans("lang.number") }}</dt>
                        <dd class="col-sm-7">{{ $payment->extended_id ?? '-' }}</dd>

                        <dt class="col-sm-5">{{ trans("lang.payment_payment_method_id") }}</dt>
                        <dd class="col-sm-7">
                            {{ $payment->payment_method_id   ?? '-' }}
                        </dd>

                        <dt class="col-sm-5">{{ trans("lang.payment_amount") }}</dt>
                        <dd class="col-sm-7">{{ $payment->amount  ?? '-' }} {{  'XOF' }}</dd>

                        <dt class="col-sm-5">{{ trans("lang.coupon_description") }}</dt>
                        <dd class="col-sm-7">{{ $payment->description }}</dd>

                        <dt class="col-sm-5">{{ trans("lang.payment_status") }}</dt>
                        <dd class="col-sm-7">{{ $payment->payment_status_id }}</dd>
                    </dl>
                </div>

                <!-- Colonne droite -->
                {{-- <div class="col-md-6">
                    <dl class="row">
                        <dt class="col-sm-5">{{ trans("lang.coupon_e_service_id") }}</dt>
                        <dd class="col-sm-7">
                            @foreach ($eServicesSelected as $id)
                                <span class="badge bg-info">{{ $eService[$id] ?? 'N/A' }}</span>
                            @endforeach
                        </dd>

                        <dt class="col-sm-5">{{ trans("lang.coupon_salon_id") }}</dt>
                        <dd class="col-sm-7">
                            @foreach ($salonsSelected as $id)
                                <span class="badge bg-secondary">{{ $salon[$id] ?? 'N/A' }}</span>
                            @endforeach
                        </dd>

                        <dt class="col-sm-5">{{ trans("lang.coupon_category_id") }}</dt>
                        <dd class="col-sm-7">
                            @foreach ($categoriesSelected as $id)
                                <span class="badge bg-warning text-dark">{{ $category[$id] ?? 'N/A' }}</span>
                            @endforeach
                        </dd>

                        <dt class="col-sm-5">{{ trans("lang.coupon_expires_at") }}</dt>
                        <dd class="col-sm-7">{{ $payment->expires_at ? \Carbon\Carbon::parse($payment->expires_at)->format('d/m/Y H:i') : '-' }}</dd>    

                        <dt class="col-sm-5">{{ trans("lang.coupon_enabled") }}</dt>
                        <dd class="col-sm-7">
                            @if($payment->enabled)
                                <span class="badge bg-success">{{ trans('lang.yes') }}</span>
                            @else
                                <span class="badge bg-danger">{{ trans('lang.no') }}</span>
                            @endif
                        </dd>
                    </dl>
                </div> --}}
            </div>

            {{-- @if($customFields)
                <hr>
                <div class="mt-4">
                    <h5><i class="bi bi-sliders2-vertical me-1"></i> {{ trans('lang.custom_field_plural') }}</h5>
                    {!! $customFields !!}
                </div>
            @endif --}}
        </div>

        <div class="card-footer d-flex justify-content-end">
            <a href="{!! route('payments.index') !!}" class="btn btn-secondary me-2">
                <i class="fas fa-arrow-left"></i> {{ trans('lang.cancel') }}
            </a>
        </div>
    </div>
</div>