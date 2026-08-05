@component('mail::message')
# {{ $statusHeadline }}

Your order {{ $order->order_number }} is now: **{{ $statusHeadline }}**.

@component('mail::button', ['url' => $statusUrl])
View order status
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
