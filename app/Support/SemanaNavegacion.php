<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Value Object que encapsula la ventana de reporte de disponibilidad.
 * El "día límite" configurado por el colegio (1=lunes...7=domingo, mismo
 * esquema que ConfiguracionColegio::getDiaDisponibilidad()) es la fecha de
 * corte de reporte, no el primer día de la ventana: la ventana teóricamente
 * son los 7 días DESPUÉS del último límite, terminando en el próximo límite
 * inclusive — el día límite mismo nunca es editable, porque para esa fecha
 * ya debió reportarse en el ciclo anterior.
 *
 * Nunca se puede reportar el día de HOY — como mínimo se reporta desde
 * mañana, incluso el mismo día límite (por eso la ventana teórica ya arranca
 * un día después del límite). Si al consultar la ventana ACTUAL el árbitro
 * llega tarde (más de un día después del límite), el inicio visible se
 * recorta a "mañana" relativo a la fecha en que entra, no a "hoy": nunca se
 * muestran días que ya pasaron ni el día de hoy. Al navegar explícitamente
 * a otra semana (parámetro de fecha), se muestra completa, sin recorte.
 * Este recorte solo aplica al formulario del ÁRBITRO (lo que puede editar).
 * Para vistas de solo lectura (ej. resumen del designador) pasar
 * $recortarAHoy=false para ver siempre la semana completa.
 *
 * $lunes/$domingo son el inicio visible/fin de esa ventana — el nombre es
 * histórico, no corresponden literalmente a lunes/domingo.
 *
 * Uso: SemanaNavegacion::desde($request->query('semana'), $diaLimite)
 */
final class SemanaNavegacion
{
    public readonly Carbon $lunes;
    public readonly Carbon $domingo;
    public readonly Carbon $inicioTeorico;
    public readonly string $semanaActualKey;
    public readonly string $semanaPrev;
    public readonly string $semanaNext;

    /** @var Collection<int, Carbon> */
    public readonly Collection $dias;

    private function __construct(Carbon $inicioTeorico, Carbon $limite, bool $esVentanaActual, int $diaLimite, bool $recortarAHoy)
    {
        $this->inicioTeorico = $inicioTeorico->copy()->startOfDay();
        $this->domingo       = $limite->copy()->addDays(7)->startOfDay();

        $manana = Carbon::tomorrow();
        $this->lunes = ($esVentanaActual && $recortarAHoy && $manana->gt($this->inicioTeorico))
            ? $manana
            : $this->inicioTeorico->copy();

        // Identidad de "la ventana vigente ahora mismo", independiente de si
        // esta instancia viene de navegar a otra semana o de recortar el inicio.
        $weekStartsAt          = $diaLimite % 7;
        $this->semanaActualKey = Carbon::now()->startOfWeek($weekStartsAt)->addDay()->toDateString();

        $this->semanaPrev = $this->inicioTeorico->copy()->subWeek()->toDateString();
        $this->semanaNext = $this->inicioTeorico->copy()->addWeek()->toDateString();

        $this->dias = collect();
        $cursor      = $this->lunes->copy();
        while ($cursor->lte($this->domingo)) {
            $this->dias->push($cursor->copy());
            $cursor->addDay();
        }
    }

    /**
     * Construye la ventana de reporte a partir de un parámetro de ruta/query
     * opcional (fecha de referencia) y el día límite configurado por el colegio.
     * Si el parámetro es nulo o inválido, usa la ventana actualmente abierta.
     *
     * @param  int   $diaLimite     1=lunes...7=domingo. Por defecto 1 (lunes).
     * @param  bool  $recortarAHoy  Si es false, la ventana actual se muestra completa
     *                              (7 días) aunque hoy caiga a mitad de camino. Usar
     *                              false en vistas de solo lectura, true en formularios
     *                              donde el árbitro reporta su propia disponibilidad.
     */
    public static function desde(?string $fechaParam, int $diaLimite = 1, bool $recortarAHoy = true): self
    {
        // Carbon numera domingo=0...sábado=6; nuestro esquema es lunes=1...domingo=7.
        // %7 alinea ambos (7 % 7 = 0 = domingo en Carbon).
        $weekStartsAt    = $diaLimite % 7;
        $esVentanaActual = $fechaParam === null;
        $referencia      = Carbon::now();

        if ($fechaParam !== null) {
            try {
                $referencia = Carbon::createFromFormat('Y-m-d', $fechaParam) ?? $referencia;
            } catch (\Throwable) {
                // Fecha inválida — silencia y usa la ventana actual.
            }
        }

        // Último límite en o antes de la fecha de referencia.
        $limite        = $referencia->copy()->startOfWeek($weekStartsAt);
        $inicioTeorico = $limite->copy()->addDay();

        return new self($inicioTeorico, $limite, $esVentanaActual, $diaLimite, $recortarAHoy);
    }

    public function esActual(): bool
    {
        return $this->inicioTeorico->toDateString() === $this->semanaActualKey;
    }
}
