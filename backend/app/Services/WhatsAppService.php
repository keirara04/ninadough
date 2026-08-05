<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Thin wrapper over Meta's WhatsApp Cloud API. Sends a free-form text
 * message — this only delivers within an open 24h customer-service
 * session (i.e. the admin has messaged the business number recently).
 * A template message (pre-approved by Meta) would be needed to reliably
 * reach outside that window; not built for MVP, revisit if admins report
 * missed notifications.
 */
class WhatsAppService
{
    public function sendText(string $toE164, string $message): array
    {
        $token = config('services.whatsapp.token');
        $phoneNumberId = config('services.whatsapp.phone_number_id');

        $response = Http::withToken($token)
            ->post("https://graph.facebook.com/v21.0/{$phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => ltrim($toE164, '+'),
                'type' => 'text',
                'text' => ['body' => $message],
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('WhatsApp send failed: '.$response->body());
        }

        return $response->json();
    }
}
