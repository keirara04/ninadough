<?php

namespace App\Actions\Orders;

use App\Actions\Inventory\DeductStockAction;
use App\Actions\Inventory\ReleaseStockAction;
use App\Exceptions\InvalidOrderTransitionException;
use App\Models\Order;
use App\Models\OrderStatusEvent;
use App\Models\User;
use App\Services\OrderStatusTransitions;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransitionOrderStatusAction
{
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
        return DB::transaction(function () use ($orderId, $toStatus, $actorType, $actorUser, $rejectionMessage, $noteInternal) {
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
    }
}
