<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public $resetCode;
    public $user;
    /**
     * Create a new message instance.
     */
    public function __construct($resetCode,$user)
    {
        $this->resetCode = $resetCode;
        $this->user = $user;

    }

    /**
     * Get the message envelope.
     */
    /*
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reset Password Mail',
        );
    }
*/
    /**
     * Get the message content definition.
     */
    /*
    public function content(): Content
    {
        return new Content(
            view: 'view.name',
        );
    }
*/
    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    /*
    public function attachments(): array
    {
        return [];
    }
    */
    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // You can customize the email subject, view, and data here
        return $this->subject('Password Reset')->view('reset')->with([
            'resetCode' => $this->resetCode,
            'user'=>$this->user
        ]);
    }
}
