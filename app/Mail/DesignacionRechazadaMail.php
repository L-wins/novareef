<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Designacion;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DesignacionRechazadaMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Designacion $designacion) {}

    public function envelope(): Envelope
    {
        $nombre  = $this->designacion->arbitro?->usuario?->nombreUsuario ?? 'El árbitro';
        $partido = $this->designacion->partido;

        return new Envelope(
            subject: "🔴 Designación rechazada — {$nombre} | {$partido->equipoLocal} vs {$partido->equipoVisitante}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.designacion-rechazada');
    }
}
