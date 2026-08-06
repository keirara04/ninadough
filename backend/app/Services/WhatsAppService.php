<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Thin wrapper over Fonnte (unofficial WhatsApp gateway, QR-linked device —
 * not Meta's official Cloud API). Chosen over Meta Cloud API for MVP: no
 * business verification step, delivers to any number immediately, no 24h
 * session-window restriction. Trade-off: runs against WhatsApp's ToS, so
 * the linked device number should be a dedicated/spare number, not the
 * owner's daily-driver WhatsApp — a ban only affects that linked number.
 */
class WhatsAppService
{
    public function sendText(string $toE164, string $message): array
    {
        $token = config('services.whatsapp.token');

        $response = Http::withHeaders(['Authorization' => $token])
            ->asForm()
            ->post('https://api.fonnte.com/send', [
                'target' => ltrim($toE164, '+'),
                'message' => $message,
            ]);

        $body = $response->json();

        if ($response->failed() || ($body['status'] ?? null) === false) {
            throw new \RuntimeException('WhatsApp send failed: '.$response->body());
        }

        return $body;
    }
}
