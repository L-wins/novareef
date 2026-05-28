<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CredencialesColegioMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $nombreColegio,
        public readonly string $urlAcceso,
        public readonly string $emailAdmin,
        public readonly string $passwordGenerado,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tus credenciales de acceso a NovaReef',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.credenciales-colegio',
        );
    }
}
