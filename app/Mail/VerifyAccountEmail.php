<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class VerifyAccountEmail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verify your Bet-Sefer account',
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.verify',
            with: [
                'name' => $this->user->name,
                'url' => URL::temporarySignedRoute(
                    'verification.verify',
                    now()->addHours(24),
                    ['user' => $this->user->ulid, 'hash' => sha1($this->user->email)],
                ),
            ],
        );
    }
}
