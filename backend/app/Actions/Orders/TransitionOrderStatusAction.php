<?php

namespace App\Actions\Orders;

use App\Actions\Inventory\DeductStockAction;
use App\Actions\Inventory\ReleaseStockAction;
use App\Exceptions\InvalidOrderTransitionException;
use App\Mail\OrderStatusUpdateMail;
use App\Models\NotificationLog;
use App\Models\Order;
use App\Models\OrderStatusEvent;
use App\Models\User;
use App\Services\OrderStatusTransitions;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class TransitionOrderStatusAction
{
    /**
     * Customer-facing status copy — only these transitions notify the
     * customer. rejected/expired/cancelled and internal states like
     * whatsapp_pending/awaiting_payment stay silent for now.
     */
    private const CUSTOMER_STATUS_COPY = [
        'payment_submitted' => 'Payment under review',
        'payment_confirmed' => 'Paid',
        'preparing' => "We're preparing your order",
        'ready_for_pickup' => 'Ready for pickup',
        'out_for_delivery' => 'Out for delivery',
    ];

    public function __construct(
        private readonly ReleaseOrderCapacityAction $releaseCapacityAction = new ReleaseOrderCapacityAction,
        private readonly ReleaseStockAction $releaseStockAction = new ReleaseStockAction,
        private readonly DeductStockAction $deductStockAction = new DeductStockAction,
    ) {}

    /**
     * Central, idempotent, transactional order status transition executor.
     * Every admin/system status change (updateStatus, payment review, the
     * expiry scheduler) must go through this — no separate unguarded path.
     */
    public function execute(
        int $orderId,
        string $toStatus,
        string $actorType,
        ?User $actorUser = null,
        ?string $rejectionMessage = null,
        ?string $noteInternal = null,
    ): Order {
        $order = DB::transaction(function () use ($orderId, $toStatus, $actorType, $actorUser, $rejectionMessage, $noteInternal) {
            $order = Order::whereKey($orderId)->lockForUpdate()->firstOrFail();
            $fromStatus = $order->status;

            // Idempotent: a transition already applied no-ops rather than reapplying or erroring.
            if ($fromStatus === $toStatus) {
                return $order;
            }

            $config = OrderStatusTransitions::config($fromStatus, $toStatus);

            if ($config === null) {
                throw new InvalidOrderTransitionException($fromStatus, $toStatus);
            }

            if (($config['system_only'] ?? false) && $actorType !== 'system') {
                throw new InvalidOrderTransitionException($fromStatus, $toStatus);
            }

            if (($config['owner_only'] ?? false) && (! $actorUser || ! $actorUser->isOwner())) {
                throw new AuthorizationException('Only the owner can cancel an order after payment has been confirmed.');
            }

            if (($config['requires_reason'] ?? false) && ! $rejectionMessage) {
                throw ValidationException::withMessages([
                    'rejection_message' => ['A rejection message is required.'],
                ]);
            }

            match ($config['effect']) {
                'release' => (function () use ($order) {
                    $this->releaseCapacityAction->releaseCapacityOnly($order);
                    $this->releaseStockAction->execute($order);
                })(),
                'deduct' => $this->deductStockAction->execute($order),
                'refund_flag' => $order->refund_required = true,
                'none' => null,
            };

            $order->status = $toStatus;

            if ($rejectionMessage !== null) {
                $order->rejection_message = $rejectionMessage;
            }

            if ($toStatus === 'payment_confirmed') {
                $order->payment_status = 'paid';
                $order->paid_at = now();
                $order->confirmed_at = now();
            } elseif ($toStatus === 'completed') {
                $order->completed_at = now();
            } elseif (in_array($toStatus, ['rejected', 'expired', 'cancelled'], true) && $order->payment_status === 'submitted') {
                $order->payment_status = 'failed';
            }

            $order->save();

            OrderStatusEvent::create([
                'order_id' => $order->id,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'actor_type' => $actorType,
                'actor_user_id' => $actorUser?->id,
                'note_internal' => $noteInternal,
            ]);

            return $order;
        });

        $this->notifyCustomerIfApplicable($order);

        return $order;
    }

    private function notifyCustomerIfApplicable(Order $order): void
    {
        $headline = self::CUSTOMER_STATUS_COPY[$order->status] ?? null;

        if (! $headline || ! $order->wasChanged('status') || ! $order->customer_email_snapshot) {
            return;
        }

        Mail::to($order->customer_email_snapshot)->queue(new OrderStatusUpdateMail($order, $headline));

        NotificationLog::create([
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'channel' => 'email',
            'template_key' => 'order_status_update',
            'recipient' => $order->customer_email_snapshot,
            'status' => 'queued',
        ]);
    }
}
