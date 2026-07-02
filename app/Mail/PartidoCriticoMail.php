<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Partido;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PartidoCriticoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Partido $partido,
        public readonly ?string $motivo = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "🚨 Partido CRÍTICO — {$this->partido->equipoLocal} vs {$this->partido->equipoVisitante}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.partido-critico');
    }
}
