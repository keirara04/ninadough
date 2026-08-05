<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class OrderStatusUpdateMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Order $order,
        public readonly string $statusHeadline,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Order {$this->order->order_number} — {$this->statusHeadline}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.order-status-update',
            with: [
                'order' => $this->order,
                'statusHeadline' => $this->statusHeadline,
                'statusUrl' => URL::temporarySignedRoute(
                    'orders.status',
                    now()->addDay(),
                    ['reference' => $this->order->order_number],
                ),
            ],
        );
    }
}
