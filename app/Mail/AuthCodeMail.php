<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AuthCodeMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param  string  $purpose  "verification" | "password_reset"
     */
    public function __construct(
        public readonly string $code,
        public readonly string $purpose = 'verification'
    ) {
    }

    public function envelope(): Envelope
    {
        $subject = $this->purpose === 'password_reset'
            ? 'Your password reset code'
            : 'Your LongLive email verification code';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(text: 'emails.auth-code');
    }
}