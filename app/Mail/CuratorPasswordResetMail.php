<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CuratorPasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $email, public string $resetUrl) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address((string) config('mail.from.address'), 'Histaryo'),
            to: [new Address($this->email)],
            subject: 'Reset password',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.curator-password-reset');
    }
}
