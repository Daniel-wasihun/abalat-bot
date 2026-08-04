<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserRegisteredMail extends Mailable implements ShouldQueue {
    use Queueable, SerializesModels;

    public $timeout = 60; // Increased for stable SMTP connection
    public $tries = 3;    // More retries for mail

    public User $user;
    public string $password;
    public $locale; // Override parent untyped property correctly

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, string $password) {
        $this->user = $user;
        $this->password = $password;
        $this->locale = app()->getLocale();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope {
        if ($this->locale) \App\Services\BackMessage::set($this->locale);
        
        $fromAddress = config('mail.from.address') ?: env('MAIL_FROM_ADDRESS', 'noreply@senbetschool.com');
        $fromName = \App\Services\BackMessage::get('app.full_name') ?: config('mail.from.name', 'Senbet School');

        return new Envelope(
            from: new \Illuminate\Mail\Mailables\Address($fromAddress, $fromName),
            subject: \App\Services\BackMessage::get('email.welcome_title'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content {
        if ($this->locale) \App\Services\BackMessage::set($this->locale);
        return new Content(
            view: 'emails.user-registered',
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
