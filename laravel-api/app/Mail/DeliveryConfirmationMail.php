<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Order;

class DeliveryConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $confirmationUrl;

    public function __construct(Order $order, string $confirmationUrl)
    {
        $this->order = $order;
        $this->confirmationUrl = $confirmationUrl;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirm Your Order Delivery - Order #' . $this->order->order_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.delivery-confirmation',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
