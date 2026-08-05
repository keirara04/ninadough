<?php

namespace App\Services;

class OrderStatusTransitions
{
    /**
     * Explicit transition table — no wildcard "* -> cancelled". Each entry:
     * 'effect' one of: none, release (pre-payment exit: release stock+capacity),
     * deduct (convert stock reservation to real deduction), refund_flag
     * (post-payment cancel: set refund_required, no stock/capacity change).
     * 'owner_only' restricts the transition to owner role (post-payment cancel).
     * 'requires_reason' forces a rejection_message to be supplied.
     * 'system_only' restricts the transition to the scheduled expiry job.
     */
    private const MAP = [
        'whatsapp_pending' => [
            'cancelled' => ['effect' => 'release'],
        ],
        'awaiting_payment' => [
            'payment_submitted' => ['effect' => 'none'],
            'expired' => ['effect' => 'release', 'system_only' => true],
            'cancelled' => ['effect' => 'release'],
        ],
        'payment_submitted' => [
            'payment_confirmed' => ['effect' => 'deduct'],
            'rejected' => ['effect' => 'release', 'requires_reason' => true],
            'cancelled' => ['effect' => 'release'],
        ],
        'payment_confirmed' => [
            'preparing' => ['effect' => 'none'],
            'cancelled' => ['effect' => 'refund_flag', 'owner_only' => true],
        ],
        'preparing' => [
            'ready_for_pickup' => ['effect' => 'none'],
            'out_for_delivery' => ['effect' => 'none'],
            'cancelled' => ['effect' => 'refund_flag', 'owner_only' => true],
        ],
        'ready_for_pickup' => [
            'completed' => ['effect' => 'none'],
            'cancelled' => ['effect' => 'refund_flag', 'owner_only' => true],
        ],
        'out_for_delivery' => [
            'completed' => ['effect' => 'none'],
            'cancelled' => ['effect' => 'refund_flag', 'owner_only' => true],
        ],
    ];

    /**
     * @return array{effect: string, owner_only?: bool, requires_reason?: bool, system_only?: bool}|null
     */
    public static function config(string $from, string $to): ?array
    {
        return self::MAP[$from][$to] ?? null;
    }

    public static function isAllowed(string $from, string $to): bool
    {
        return self::config($from, $to) !== null;
    }
}
