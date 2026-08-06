<?php

namespace App\Jobs;

use App\Models\NotificationLog;
use App\Models\Order;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyAdminPaymentSubmitted implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly int $orderId) {}

    public function handle(WhatsAppService $whatsAppService): void
    {
        $order = Order::find($this->orderId);
        if (! $order) {
            return;
        }

        $message = sprintf(
            "Payment review needed\nOrder %s, RM%s\nPlease review and confirm in the admin dashboard.",
            $order->order_number,
            number_format($order->total_sen / 100, 2),
        );

        $recipients = User::query()
            ->where('is_active', true)
            ->whereNotNull('phone_e164')
            ->get();

        foreach ($recipients as $recipient) {
            $this->notifyOne($whatsAppService, $order, $recipient, $message);
        }
    }

    private function notifyOne(WhatsAppService $whatsAppService, Order $order, User $recipient, string $message): void
    {
        try {
            $result = $whatsAppService->sendText($recipient->phone_e164, $message);

            NotificationLog::create([
                'order_id' => $order->id,
                'channel' => 'whatsapp',
                'template_key' => 'admin_payment_submitted',
                'recipient' => $recipient->phone_e164,
                'status' => 'sent',
                'provider_message_id' => $result['id'][0] ?? null,
                'sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            NotificationLog::create([
                'order_id' => $order->id,
                'channel' => 'whatsapp',
                'template_key' => 'admin_payment_submitted',
                'recipient' => $recipient->phone_e164,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}
