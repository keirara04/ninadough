@component('mail::message')
# Order confirmed

Thanks for your order, {{ $order->customer_name_snapshot }}!

**Order number:** {{ $order->order_number }}
**Total:** RM{{ number_format($order->total_sen / 100, 2) }}
**Fulfilment:** {{ ucfirst($order->fulfilment_method) }}

@if ($order->status === 'awaiting_payment')
Next step: transfer the total and upload your payment receipt. We'll review and confirm your payment, then start preparing your order.
@endif

@component('mail::button', ['url' => $statusUrl])
View order status
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
