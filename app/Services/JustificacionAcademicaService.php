<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AsistenciaAcademica;
use App\Models\JustificacionAcademica;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class JustificacionAcademicaService
{
    /**
     * Registra la justificación de una inasistencia. Puede presentarse el
     * mismo día de la sesión o hasta 3 días después (fechaLimiteJustificacion
     * de la sesión) — pasado ese plazo, CerrarJustificacionesVencidasJob
     * cierra la posibilidad automáticamente.
     *
     * @param  array{motivo: string, documentoPdf?: ?string}  $datos
     *
     * @throws \RuntimeException  Si la asistencia no está en 'ausente', ya
     *     tiene una justificación registrada, o el plazo ya venció.
     */
    public function crear(AsistenciaAcademica $asistencia, array $datos): JustificacionAcademica
    {
        if ($asistencia->estadoAsistencia !== AsistenciaAcademica::ESTADO_AUSENTE) {
            throw new \RuntimeException('Solo se puede justificar una inasistencia marcada como ausente.');
        }

        if ($asistencia->justificacion()->exists()) {
            throw new \RuntimeException('Esta inasistencia ya tiene una justificación registrada.');
        }

        $sesion      = $asistencia->sesion;
        $fechaLimite = $sesion->fechaLimiteJustificacion;

        if (now()->gt($fechaLimite->copy()->endOfDay())) {
            throw new \RuntimeException('El plazo para justificar esta inasistencia ya venció.');
        }

        return DB::transaction(function () use ($asistencia, $datos, $sesion, $fechaLimite): JustificacionAcademica {
            $justificacion = JustificacionAcademica::create([
                'idColegio'            => $asistencia->idColegio,
                'idAsistencia'         => $asistencia->idAsistencia,
                'idArbitro'            => $asistencia->idArbitro,
                'motivo'               => $datos['motivo'],
                'documentoPdf'         => $datos['documentoPdf'] ?? null,
                'estadoJustificacion'  => JustificacionAcademica::ESTADO_PENDIENTE,
                'fechaLimite'          => $fechaLimite->toDateString(),
            ]);

            $asistencia->update(['estadoAsistencia' => AsistenciaAcademica::ESTADO_JUSTIFICACION_PENDIENTE]);

            return $justificacion;
        });
    }

    /**
     * Aprueba la justificación — basta con que la apruebe uno de los roles
     * autorizados (instructor, ejecutivo o sanciones).
     *
     * @throws \RuntimeException  Si ya fue revisada.
     */
    public function aprobar(JustificacionAcademica $justificacion, User $revisor): void
    {
        if (! $justificacion->estaPendiente()) {
            throw new \RuntimeException('Esta justificación ya fue revisada.');
        }

        DB::transaction(function () use ($justificacion, $revisor): void {
            $justificacion->update([
                'estadoJustificacion' => JustificacionAcademica::ESTADO_APROBADA,
                'idUsuarioRevision'   => $revisor->idUsuario,
                'fechaRevision'       => now(),
            ]);

            $justificacion->asistencia()->update(['estadoAsistencia' => AsistenciaAcademica::ESTADO_JUSTIFICADO]);
        });
    }

    /**
     * @throws \RuntimeException  Si ya fue revisada.
     */
    public function rechazar(JustificacionAcademica $justificacion, string $motivoRechazo, User $revisor): void
    {
        if (! $justificacion->estaPendiente()) {
            throw new \RuntimeException('Esta justificación ya fue revisada.');
        }

        DB::transaction(function () use ($justificacion, $motivoRechazo, $revisor): void {
            $justificacion->update([
                'estadoJustificacion' => JustificacionAcademica::ESTADO_RECHAZADA,
                'motivoRechazo'       => $motivoRechazo,
                'idUsuarioRevision'   => $revisor->idUsuario,
                'fechaRevision'       => now(),
            ]);

            $justificacion->asistencia()->update(['estadoAsistencia' => AsistenciaAcademica::ESTADO_JUSTIFICACION_RECHAZADA]);
        });
    }
}
