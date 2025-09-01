@component('mail::message')
{{-- Greeting --}}
@if (! empty($greeting))
# {{ $greeting }}
@else
@if ($level === 'error')
# @lang('Whoops!')
@else
# @lang('Hello!')
@endif
@endif

{{-- Intro Lines --}}
@foreach ($introLines as $line)
{{ $line }}

@endforeach
@component('mail::panel')
@foreach($purchase->e_services as $eService)
<tr>
<td><b>{{$eService->name}}</b></td>
<td class="text-right">{{__('lang.by')}} {{$purchase->salon->name}}</td>
</tr>
@endforeach
@endcomponent

@component('mail::panel')
<tr>
    <td><b>{{__('lang.purchase_purchase_at')}}</b></td>
    <td class="text-right"><small>{{$purchase->purchase_at}}</small></td>
</tr>
@endcomponent
@component('mail::panel')
<tr>
<td><b>{{__('lang.purchase_total')}}</b></td>
<td class="text-right"><h3 class="text-right">{!! getPrice($purchase->getTotal()) !!}</h3></td>
</tr>
<tr>
<td><b>{{__('lang.payment_status')}}</b></td>
<td class="text-right"><small>{{empty(!$purchase->payment) ? $purchase->payment->paymentStatus->status : '-'}}</small></td>
</tr>
<tr>
<td><b>{{__('lang.payment_method')}}</b></td>
<td class="text-right"><small>{{empty(!$purchase->payment) ? $purchase->payment->paymentMethod->name : '-'}}</small></td>
</tr>
@endcomponent
{{-- Action Button --}}
@isset($actionText)
<?php
switch ($level) {
case 'success':
case 'error':
$color = $level;
break;
default:
$color = 'primary';
}
?>
@component('mail::button', ['url' => $actionUrl, 'color' => $color])
{{ $actionText }}
@endcomponent
@endisset

{{-- Outro Lines --}}
@foreach ($outroLines as $line)
{{ $line }}

@endforeach

{{-- Salutation --}}
@if (! empty($salutation))
{{ $salutation }}
@else
@lang('Regards'),<br>
{{ setting('app_name',config('app.name')) }}
@endif

{{-- Subcopy --}}
@isset($actionText)
@slot('subcopy')
@lang(
"If you’re having trouble clicking the \":actionText\" button, copy and paste the URL below\n".
'into your web browser:',
[
'actionText' => $actionText,
]
) <span class="break-all">[{{ $displayableActionUrl }}]({{ $actionUrl }})</span>
@endslot
@endisset
@endcomponent
