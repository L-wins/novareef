<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\AsistenciaActualizadaEvent;
use App\Models\Arbitro;
use App\Models\AsistenciaAcademica;
use App\Models\SesionAcademica;

final class AsistenciaAcademicaService
{
    /**
     * El árbitro marca su propia asistencia desde su perfil, en modo manual.
     *
     * @throws \RuntimeException  Si la sesión no está abierta o no es modo manual.
     */
    public function marcarWeb(AsistenciaAcademica $asistencia): void
    {
        $sesion = $asistencia->sesion;

        if (! $sesion->sesionAbierta || $sesion->modoAsistencia !== SesionAcademica::MODO_MANUAL) {
            throw new \RuntimeException('La sesión no está abierta para marcar asistencia.');
        }

        $asistencia->update([
            'estadoAsistencia' => AsistenciaAcademica::ESTADO_PRESENTE,
            'horaMarca'        => now(),
            'registradoPor'    => AsistenciaAcademica::REGISTRADO_ARBITRO,
        ]);

        AsistenciaActualizadaEvent::dispatch($asistencia->fresh());
    }

    /**
     * Registra la asistencia leyendo el carné (codigoCarnet) desde la
     * terminal del instructor en modo scanner.
     *
     * @throws \RuntimeException  Si la sesión no está abierta, no es modo
     *     scanner, el código no corresponde a ningún árbitro del colegio, o
     *     el árbitro no aplica a esta sesión.
     */
    public function registrarPorScanner(SesionAcademica $sesion, string $codigoCarnet): AsistenciaAcademica
    {
        if (! $sesion->sesionAbierta || $sesion->modoAsistencia !== SesionAcademica::MODO_SCANNER) {
            throw new \RuntimeException('La sesión no está abierta para registro por scanner.');
        }

        $arbitro = Arbitro::where('idColegio', $sesion->idColegio)
            ->where('codigoCarnet', $codigoCarnet)
            ->first();

        if ($arbitro === null) {
            throw new \RuntimeException('No existe ningún árbitro con ese código de carné en este colegio.');
        }

        $asistencia = AsistenciaAcademica::where('idSesion', $sesion->idSesion)
            ->where('idArbitro', $arbitro->idArbitro)
            ->first();

        if ($asistencia === null) {
            throw new \RuntimeException('Este árbitro no aplica a esta sesión.');
        }

        $asistencia->update([
            'estadoAsistencia' => AsistenciaAcademica::ESTADO_PRESENTE,
            'horaMarca'        => now(),
            'registradoPor'    => AsistenciaAcademica::REGISTRADO_INSTRUCTOR,
        ]);

        $asistencia = $asistencia->fresh(['arbitro.usuario']);

        AsistenciaActualizadaEvent::dispatch($asistencia);

        return $asistencia;
    }

    /**
     * El instructor corrige manualmente una marca — solo permitido mientras
     * la sesión sigue abierta (antes de confirmar/cerrar la lista).
     *
     * @throws \RuntimeException  Si la sesión ya no está abierta o el estado no es válido.
     */
    public function corregirMarca(AsistenciaAcademica $asistencia, string $nuevoEstado): AsistenciaAcademica
    {
        if (! $asistencia->sesion->sesionAbierta) {
            throw new \RuntimeException('Solo se puede corregir una marca mientras la sesión está abierta.');
        }

        if (! in_array($nuevoEstado, [AsistenciaAcademica::ESTADO_PRESENTE, AsistenciaAcademica::ESTADO_AUSENTE], true)) {
            throw new \RuntimeException('Estado de asistencia inválido.');
        }

        $asistencia->update([
            'estadoAsistencia' => $nuevoEstado,
            'horaMarca'        => $nuevoEstado === AsistenciaAcademica::ESTADO_PRESENTE ? now() : null,
            'registradoPor'    => AsistenciaAcademica::REGISTRADO_INSTRUCTOR,
        ]);

        $asistencia = $asistencia->fresh(['arbitro.usuario']);

        AsistenciaActualizadaEvent::dispatch($asistencia);

        return $asistencia;
    }
}
