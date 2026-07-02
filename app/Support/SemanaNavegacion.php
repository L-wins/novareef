<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Value Object que encapsula la semana actualmente navegada.
 * Recibe un parámetro de fecha opcional (YYYY-MM-DD) y expone
 * los datos de navegación que necesita cualquier vista de semana.
 *
 * Uso: SemanaNavegacion::desde($request->query('semana'))
 */
final class SemanaNavegacion
{
    public readonly Carbon $lunes;
    public readonly Carbon $domingo;
    public readonly string $semanaActualKey;
    public readonly string $semanaPrev;
    public readonly string $semanaNext;

    /** @var Collection<int, Carbon> */
    public readonly Collection $dias;

    private function __construct(Carbon $lunes)
    {
        $this->lunes          = $lunes->copy()->startOfDay();
        $this->domingo        = $lunes->copy()->endOfWeek(Carbon::SUNDAY)->startOfDay();
        $this->semanaActualKey = Carbon::now()->startOfWeek(Carbon::MONDAY)->toDateString();
        $this->semanaPrev     = $lunes->copy()->subWeek()->toDateString();
        $this->semanaNext     = $lunes->copy()->addWeek()->toDateString();
        $this->dias           = collect(range(0, 6))->map(fn (int $i) => $lunes->copy()->addDays($i));
    }

    /**
     * Construye la semana a partir de un parámetro de ruta/query opcional.
     * Si el parámetro es nulo o inválido, usa la semana actual.
     */
    public static function desde(?string $fechaParam): self
    {
        $lunes = Carbon::now()->startOfWeek(Carbon::MONDAY);

        if ($fechaParam !== null) {
            try {
                $lunes = Carbon::createFromFormat('Y-m-d', $fechaParam)
                    ?->startOfWeek(Carbon::MONDAY)
                    ?? $lunes;
            } catch (\Throwable) {
                // Fecha inválida — silencia y usa la semana actual.
            }
        }

        return new self($lunes);
    }

    public function esActual(): bool
    {
        return $this->lunes->toDateString() === $this->semanaActualKey;
    }
}
