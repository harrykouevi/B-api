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

{{-- Type de rappel et destinataire --}}
@component('mail::panel')
<tr>
<td><b>{{ __('Rappel') }}</b></td>
<td class="text-right"><small>{{ ucfirst($reminderType) }}</small></td>
</tr>
<tr>
<td><b>{{ __('Destinataire') }}</b></td>
<td class="text-right"><small>{{ $recipient->name ?? $recipient }}</small></td>
</tr>
@endcomponent

{{-- Services détaillés --}}
@component('mail::panel')
@foreach($services as $service)
<tr>
<td><b>{{ $service['name'] }}</b></td>
<td class="text-right">{{ __('par') }} {{ $service['provider'] ?? $booking->salon->name }}</td>
<td class="text-right">{!! getPrice($service['price']) !!}</td>
</tr>
@endforeach
@endcomponent

{{-- Localisation & temps restant --}}
@component('mail::panel')
<tr>
<td><b>{{ __('Lieu du rendez-vous') }}</b></td>
<td class="text-right"><small>{{ $locationDetails['address'] ?? $booking->address->address }}</small></td>
</tr>
<tr>
<td><b>{{ __('Temps restant') }}</b></td>
<td class="text-right"><small>{{ $timeUntil }}</small></td>
</tr>
@endcomponent

{{-- Prix global --}}
@component('mail::panel')
<tr>
<td><b>{{ __('Détails du prix') }}</b></td>
<td class="text-right"><h3>{!! getPrice($priceDetails['total'] ?? $booking->getTotal()) !!}</h3></td>
</tr>
<tr>
<td><b>{{ __('Méthode de paiement') }}</b></td>
<td class="text-right"><small>{{ $priceDetails['method'] ?? ($booking->payment->paymentMethod->name ?? '-') }}</small></td>
</tr>
<tr>
<td><b>{{ __('Statut du paiement') }}</b></td>
<td class="text-right"><small>{{ $priceDetails['status'] ?? ($booking->payment->paymentStatus->status ?? '-') }}</small></td>
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
{{ setting('app_name', config('app.name')) }}
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
