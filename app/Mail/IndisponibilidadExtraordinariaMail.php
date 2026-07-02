<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Arbitro;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class IndisponibilidadExtraordinariaMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Arbitro    $arbitro,
        public readonly string     $fecha,
        public readonly string     $franja,
        public readonly string     $motivo,
        public readonly Collection $partidosAfectados,
    ) {}

    public function envelope(): Envelope
    {
        $nombre = $this->arbitro->usuario?->nombreUsuario ?? 'Árbitro';

        return new Envelope(
            subject: '⚠️ Indisponibilidad extraordinaria — ' . $nombre,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.indisponibilidad-extraordinaria',
        );
    }
}
