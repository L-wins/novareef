<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Arbitro;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PagoArbitroRealizadoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Arbitro $arbitro,
        public readonly float   $netoDesembolsado,
        public readonly float   $totalDeudasNeteadas,
        public readonly string  $idLotePago,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '💰 Pago de nómina registrado — NovaReef',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.pago-arbitro-realizado');
    }
}
