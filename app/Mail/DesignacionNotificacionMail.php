<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Designacion;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DesignacionNotificacionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Designacion $designacion) {}

    public function envelope(): Envelope
    {
        $partido = $this->designacion->partido;

        return new Envelope(
            subject: "⚽ Nueva designación — {$partido->equipoLocal} vs {$partido->equipoVisitante}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.designacion-notificacion');
    }
}
