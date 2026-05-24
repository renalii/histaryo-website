<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CuratorAccountCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $email,
        public string $plainPassword,
        public string $landmarkLabel,
        public string $changePasswordUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [
                new Address(
                    $this->email,
                    trim($this->firstName.' '.$this->lastName) ?: $this->email,
                ),
            ],
            subject: 'Your Histaryo curator account is ready',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.curator-account-created',
        );
    }
}
