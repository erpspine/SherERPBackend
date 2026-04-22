<?php

namespace App\Mail;

use App\Models\FuelRequisition;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FuelRequisitionCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly FuelRequisition $fuelRequisition,
        public readonly User $requestedBy,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Fuel Requisition Submitted - ' . config('app.name') . ' | Lead ' . ($this->fuelRequisition->lead->booking_ref ?? ('#' . $this->fuelRequisition->lead_id)),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.fuel-requisition-created',
        );
    }
}
