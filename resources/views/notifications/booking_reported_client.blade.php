@component('mail::message')
# {{ trans('lang.notification_booking_reported_client_greeting') }}

{{ trans('lang.notification_booking_reported_client_line1', ['booking_id' => $originalBooking->id]) }}

{{ trans('lang.notification_booking_reported_client_line2', [
    'new_date' => $newBooking->booking_at->format('d/m/Y'),
    'new_time' => $newBooking->booking_at->format('H:i')
]) }}

@component('mail::button', ['url' => route('bookings.show', $newBooking->id)])
{{ trans('lang.booking_details') }}
@endcomponent

{{ trans('lang.thanks') }}<br>
{{ setting('app_name', '') }}
@endcomponent