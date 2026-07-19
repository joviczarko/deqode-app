<?php

namespace App\Mail;

use App\Models\SignupIntent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class SignupIntentVerificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly SignupIntent $intent,
    ) {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirm your email — DeQode signup',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.auth.signup-intent-verification',
            with: [
                'verifyUrl' => URL::temporarySignedRoute(
                    'signup.verify',
                    $this->intent->expires_at,
                    ['token' => $this->intent->token],
                ),
            ],
        );
    }
}
