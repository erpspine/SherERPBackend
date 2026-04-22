<?php

namespace App\Mail;

use App\Models\FuelRequisition;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FuelRequisitionRespondedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly FuelRequisition $fuelRequisition,
        public readonly User $responder,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Fuel Requisition ' . ($this->fuelRequisition->status ?? 'Updated') . ' - ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.fuel-requisition-responded',
        );
    }
}
