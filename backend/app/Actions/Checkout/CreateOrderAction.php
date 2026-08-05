<?php

namespace App\Actions\Checkout;

use App\Actions\Inventory\ReserveStockAction;
use App\Mail\OrderConfirmationMail;
use App\Models\BusinessSetting;
use App\Models\Customer;
use App\Models\NotificationLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemOptionValue;
use App\Models\OrderStatusEvent;
use App\Models\Payment;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CreateOrderAction
{
    public function __construct(
        private readonly PriceCartAction $priceCartAction = new PriceCartAction,
        private readonly ReservePreorderCapacityAction $reserveCapacityAction = new ReservePreorderCapacityAction,
        private readonly ReserveStockAction $reserveStockAction = new ReserveStockAction,
        private readonly ReserveTimeSlotCapacityAction $reserveTimeSlotAction = new ReserveTimeSlotCapacityAction,
    ) {}

    /**
     * @param  array{
     *     items: array<int, array{product_variant_id: int, quantity: int}>,
     *     preorder_date: string,
     *     checkout_channel: string,
     *     fulfilment_method: string,
     *     delivery_address?: array|null,
     *     idempotency_key: string,
     *     customer: array{name: string, phone_e164: string, email?: string|null},
     * }  $payload
     */
    public function execute(array $payload): Order
    {
        $existing = Order::where('idempotency_key', $payload['idempotency_key'])->first();
        if ($existing) {
            return $existing;
        }

        $quote = $this->priceCartAction->execute(
            items: $payload['items'],
            orderDate: $payload['preorder_date'],
            fulfilmentMethod: $payload['fulfilment_method'],
            deliveryPostcode: $payload['delivery_address']['postcode'] ?? null,
        );

        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                $order = DB::transaction(function () use ($payload, $quote) {
                    $this->reserveCapacityAction->execute(
                        $quote->preorderDateId,
                        $quote->totalCapacityUnits,
                        $payload['fulfilment_method'],
                    );

                    if (! empty($payload['time_slot_id'])) {
                        $this->reserveTimeSlotAction->execute(
                            $payload['time_slot_id'],
                            $quote->preorderDateId,
                            $payload['fulfilment_method'],
                        );
                    }

                    $this->reserveStockAction->execute($payload['items']);

                    $customer = Customer::firstOrCreate(
                        ['phone_e164' => $payload['customer']['phone_e164']],
                        ['name' => $payload['customer']['name'], 'email' => $payload['customer']['email'] ?? null]
                    );
                    $customer->forceFill(['last_order_at' => now()])->save();

                    $isWhatsapp = $payload['checkout_channel'] === 'whatsapp';

                    $order = Order::create([
                        'ulid' => (string) Str::ulid(),
                        'order_number' => $this->generateOrderNumber(),
                        'customer_id' => $customer->id,
                        'preorder_date_id' => $quote->preorderDateId,
                        'time_slot_id' => $payload['time_slot_id'] ?? null,
                        'checkout_channel' => $payload['checkout_channel'],
                        'fulfilment_method' => $payload['fulfilment_method'],
                        'delivery_zone_id' => $quote->deliveryZoneId,
                        'customer_name_snapshot' => $payload['customer']['name'],
                        'customer_phone_snapshot' => $payload['customer']['phone_e164'],
                        'customer_email_snapshot' => $payload['customer']['email'] ?? null,
                        'delivery_address' => $payload['delivery_address'] ?? null,
                        'pickup_instruction_snapshot' => $payload['fulfilment_method'] === 'pickup'
                            ? BusinessSetting::where('key', 'pickup_instructions')->first()?->value
                            : null,
                        'subtotal_sen' => $quote->subtotalSen,
                        'delivery_fee_sen' => $quote->deliveryFeeSen,
                        'discount_sen' => 0,
                        'total_sen' => $quote->totalSen,
                        'total_capacity_units' => $quote->totalCapacityUnits,
                        'status' => $isWhatsapp ? 'whatsapp_pending' : 'awaiting_payment',
                        'payment_status' => $isWhatsapp ? 'not_required' : 'awaiting_payment',
                        'payment_method' => $payload['payment_method'] ?? null,
                        'expires_at' => $isWhatsapp ? now()->addMinutes($this->whatsappReservationMinutes()) : null,
                        'idempotency_key' => $payload['idempotency_key'],
                        'notes' => $payload['notes'] ?? null,
                        'card_message' => $payload['card_message'] ?? null,
                        'allergies_note' => $payload['allergies_note'] ?? null,
                        'hide_price_on_package' => $payload['hide_price_on_package'] ?? false,
                    ]);

                    foreach ($quote->lines as $sortOrder => $line) {
                        $orderItem = OrderItem::create([
                            'order_id' => $order->id,
                            'product_id' => $line['product_id'],
                            'product_variant_id' => $line['product_variant_id'],
                            'product_name_snapshot' => $line['product_name'],
                            'variant_name_snapshot' => $line['variant_name'],
                            'unit_price_sen' => $line['unit_price_sen'],
                            'quantity' => $line['quantity'],
                            'capacity_units_each' => $line['capacity_units_each'],
                            'line_total_sen' => $line['line_total_sen'],
                            'sort_order' => $sortOrder,
                        ]);

                        foreach ($line['option_values'] as $optionSortOrder => $optionValue) {
                            OrderItemOptionValue::create([
                                'order_item_id' => $orderItem->id,
                                'option_group_name_snapshot' => $optionValue['option_group_name'],
                                'option_value_name_snapshot' => $optionValue['option_value_name'],
                                'sort_order' => $optionSortOrder,
                            ]);
                        }
                    }

                    OrderStatusEvent::create([
                        'order_id' => $order->id,
                        'from_status' => null,
                        'to_status' => $order->status,
                        'actor_type' => 'system',
                    ]);

                    if (! $isWhatsapp) {
                        Payment::create([
                            'order_id' => $order->id,
                            'method' => 'bank_transfer',
                            'amount_sen' => $order->total_sen,
                            'currency' => 'MYR',
                            'status' => 'pending',
                        ]);
                    }

                    return $order;
                });

                if ($order->customer_email_snapshot) {
                    Mail::to($order->customer_email_snapshot)->queue(new OrderConfirmationMail($order));

                    NotificationLog::create([
                        'order_id' => $order->id,
                        'customer_id' => $order->customer_id,
                        'channel' => 'email',
                        'template_key' => 'order_confirmation',
                        'recipient' => $order->customer_email_snapshot,
                        'status' => 'queued',
                    ]);
                }

                return $order;
            } catch (QueryException $e) {
                if (! $this->isUniqueViolation($e)) {
                    throw $e;
                }

                $existing = Order::where('idempotency_key', $payload['idempotency_key'])->first();
                if ($existing) {
                    return $existing;
                }

                // Unique violation was on order_number instead — regenerate and retry.
                continue;
            }
        }

        throw new \RuntimeException('Failed to create order after retrying order_number generation.');
    }

    private function isUniqueViolation(QueryException $e): bool
    {
        return ($e->errorInfo[0] ?? null) === '23505';
    }

    private function generateOrderNumber(): string
    {
        return 'ND-'.strtoupper(Str::random(6));
    }

    private function whatsappReservationMinutes(): int
    {
        return (int) (BusinessSetting::where('key', 'default_whatsapp_reservation_minutes')->first()?->value ?? 20);
    }
}
