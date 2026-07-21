<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\SolicitudArco;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SolicitudArcoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly SolicitudArco $solicitud) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nueva solicitud de derechos ARCO — NovaReef',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.solicitud-arco',
            with: [
                'nombreSolicitante' => $this->solicitud->usuario->nombreUsuario,
                'tipo'              => $this->solicitud->tipo,
                'mensaje'           => $this->solicitud->mensaje,
            ],
        );
    }
}
