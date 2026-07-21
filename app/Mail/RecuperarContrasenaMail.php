<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RecuperarContrasenaMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $nombreUsuario,
        public readonly string $urlRestablecimiento,
        public readonly int    $minutosExpiracion,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Recupera tu contraseña de NovaReef',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.recuperar-contrasena',
        );
    }
}
