<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetOtpMail extends Mailable implements ShouldQueue {
    use Queueable, SerializesModels;

    public $otp;
    public string $mailLocale;

    /**
     * Create a new message instance.
     */
    public function __construct($otp) {
        $this->otp = $otp;
        $this->mailLocale = app()->getLocale();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope {
        \App\Services\BackMessage::set($this->mailLocale);
        $fromAddress = config('mail.from.address') ?: env('MAIL_FROM_ADDRESS', 'noreply@senbetschool.com');
        $fromName = \App\Services\BackMessage::get('app.full_name') ?: config('mail.from.name', 'Senbet School');

        return new Envelope(
            from: new \Illuminate\Mail\Mailables\Address($fromAddress, $fromName),
            subject: \App\Services\BackMessage::get('email.reset_title'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content {
        \App\Services\BackMessage::set($this->mailLocale);
        return new Content(
            view: 'emails.password-reset-otp',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array {
        return [];
    }
}
