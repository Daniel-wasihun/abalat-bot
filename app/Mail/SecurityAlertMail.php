<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SecurityAlertMail extends Mailable implements ShouldQueue {
    use Queueable, SerializesModels;

    public $name;
    public $device_name;
    public $location;
    public $time;
    public $approve_url;
    public $terminate_url;
    public $lock_url;

    /**
     * Create a new message instance.
     */
    public function __construct($data) {
        $this->name = $data['name'];
        $this->device_name = $data['device_name'];
        $this->location = $data['location'];
        $this->time = $data['time'];
        $this->approve_url = $data['approve_url'];
        $this->terminate_url = $data['terminate_url'];
        $this->lock_url = $data['lock_url'];
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope {
        return new Envelope(
            subject: 'Security Alert: New Device Login Detected',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content {
        return new Content(
            markdown: 'emails.security_alert',
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
