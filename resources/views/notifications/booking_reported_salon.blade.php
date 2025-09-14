@component('mail::message')
# {{ trans('lang.notification_booking_reported_salon_greeting') }}

{{ trans('lang.notification_booking_reported_salon_line1', [
    'client_name' => $originalBooking->user->name,
    'booking_id' => $originalBooking->id
]) }}

{{ trans('lang.notification_booking_reported_salon_line2', [
    'services' => $newBooking->e_services->pluck('name')->implode(', '),
    'new_date' => $newBooking->booking_at->format('d/m/Y'),
    'new_time' => $newBooking->booking_at->format('H:i')
]) }}

@component('mail::button', ['url' => route('bookings.show', $newBooking->id)])
{{ trans('lang.booking_details') }}
@endcomponent

{{ trans('lang.thanks') }}<br>
{{ setting('app_name', '') }}
@endcomponent