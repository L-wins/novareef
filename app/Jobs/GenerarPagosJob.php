<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Partido;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerarPagosJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly Partido $partido) {}

    public function handle(): void
    {
        // M06 completará esta lógica de nómina
        Log::info("M06: Generar pagos para partido {$this->partido->idPartido}");
    }
}
