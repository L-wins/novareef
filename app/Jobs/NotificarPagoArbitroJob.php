<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\PagoArbitroRealizadoMail;
use App\Models\Arbitro;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificarPagoArbitroJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries     = 3;
    public array $backoff = [30, 60, 120];

    public function __construct(
        public readonly Arbitro $arbitro,
        public readonly float   $netoDesembolsado,
        public readonly float   $totalDeudasNeteadas,
        public readonly string  $idLotePago,
    ) {}

    public function handle(): void
    {
        $email = $this->arbitro->usuario?->emailUsuario;

        if (! $email) {
            return;
        }

        try {
            Mail::to($email)->send(new PagoArbitroRealizadoMail(
                $this->arbitro,
                $this->netoDesembolsado,
                $this->totalDeudasNeteadas,
                $this->idLotePago,
            ));
        } catch (\Throwable $e) {
            Log::error("NotificarPagoArbitroJob: fallo email. idArbitro={$this->arbitro->idArbitro}, lote={$this->idLotePago}", [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
