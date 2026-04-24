<?php

namespace App\Mail;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LeadCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Lead $lead,
        public readonly ?User $createdBy,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Lead Created — ' . config('app.name') . ' | ' . $this->lead->booking_ref,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.lead-created',
        );
    }
}
