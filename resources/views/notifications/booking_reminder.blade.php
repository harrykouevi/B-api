{{-- resources/views/notifications/booking_reminder.blade.php --}}
@component('mail::message')
# {{ $greeting }}

{{ $reminderType === 'confirmation' ? '✅' : '⏰' }} **Détails de votre réservation :**

@component('mail::panel')
📅 **Date :** {{ \Carbon\Carbon::parse($booking->booking_at)->format('d/m/Y à H:i') }}  
💇 **Services :** {{ collect($services)->pluck('name')->implode(', ') }}  
🏪 **Salon :** {{ $booking->salon->name ?? 'Non défini' }}  
📍 **Lieu :** {{ $locationDetails['type'] === 'salon' ? 'Au salon' : 'À domicile' }}  
💰 **Prix :** {{ number_format($priceDetails['total'], 2) }} €
@endcomponent

@if($booking->hint)
📝 **Note spéciale :** {{ $booking->hint }}
@endif

⏱️ **Temps restant :** {{ $timeUntil['message'] }}

@component('mail::button', ['url' => $actionUrl])
{{ $actionText }}
@endcomponent

Merci de votre confiance !

L'équipe {{ config('app.name') }}
@endcomponent