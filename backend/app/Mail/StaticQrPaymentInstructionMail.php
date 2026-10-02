<?php

namespace App\Mail;

use App\Models\CustomerOrder;
use App\Models\RefPaymentMerch;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StaticQrPaymentInstructionMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public CustomerOrder $order,
        public ?RefPaymentMerch $merchant = null
    ) {
        if (! $this->merchant) {
            $this->merchant = RefPaymentMerch::where('code', 'gotyme')->first()
                ?? RefPaymentMerch::first();
        }
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $merchantName = $this->merchant?->name ?? 'Static QR';

        return new Envelope(
            subject: "Action Required: Pay Order #{$this->order->order_number} via {$merchantName} QR",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.static-qr-payment',
            with: [
                'order' => $this->order,
                'merchant' => $this->merchant,
            ],
        );
    }

    /**
     * Attach the corresponding static QR image file to the email.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];

        if ($this->merchant && ! empty($this->merchant->qr_image_url)) {
            $relativePath = ltrim($this->merchant->qr_image_url, '/');
            $fullPath = public_path($relativePath);

            if (! file_exists($fullPath)) {
                $fullPath = storage_path('app/public/'.str_replace('images/', '', $relativePath));
            }

            if (file_exists($fullPath)) {
                $filename = basename($fullPath);
                $mimeType = mime_content_type($fullPath) ?: 'image/png';

                $attachments[] = Attachment::fromPath($fullPath)
                    ->as($filename)
                    ->withMime($mimeType);
            }
        }

        return $attachments;
    }
}
