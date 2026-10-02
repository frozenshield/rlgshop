<?php

namespace App\Mail;

use App\Models\AbandonedCartReminder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AbandonedCartReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public AbandonedCartReminder $cart,
        public ?string $recoveryUrl = null
    ) {
        if (! $this->recoveryUrl) {
            $frontendUrl = config('app.frontend_url', config('app.url', 'http://localhost:5173'));
            $this->recoveryUrl = rtrim($frontendUrl, '/').'/checkout?recover='.$cart->recovery_token.'&promo='.($cart->discount_code ?? 'RECOVER10');
        }
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Did you leave something behind? Take 10% OFF your order at RLG Hobby Shop',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.abandoned-cart-reminder',
            with: [
                'cart' => $this->cart,
                'recoveryUrl' => $this->recoveryUrl,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}

