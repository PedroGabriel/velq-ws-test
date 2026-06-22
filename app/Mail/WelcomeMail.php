<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Standard Mailable sent on signup via the default SMTP mailer
 * (config/mail.php), which Velq points at its mailpit catcher through the
 * MAIL_HOST / MAIL_PORT env it injects. No Velq-specific transport.
 */
class WelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to the Velq guestbook',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.welcome',
        );
    }
}
