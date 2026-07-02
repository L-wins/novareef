<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Designacion;
use App\Models\Partido;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PartidoAplazadoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Partido    $partido,
        public readonly Designacion $designacion,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "⏸️ Partido aplazado — {$this->partido->equipoLocal} vs {$this->partido->equipoVisitante}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.partido-aplazado');
    }
}
