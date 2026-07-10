<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\SesionAcademicaCerradaEvent;
use App\Models\Arbitro;
use App\Models\AsistenciaAcademica;
use App\Models\SesionAcademica;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class SesionAcademicaService
{
    /**
     * Crea la sesión académica y genera automáticamente un registro de
     * asistencia en estado 'ausente' para cada árbitro que aplique según el
     * criterio de la sesión (todos los del colegio, o solo una categoría).
     *
     * @param  array{
     *     idTipoSesion: int, modalidad: string, urlVirtual?: ?string, tema: string,
     *     descripcion?: ?string, lugar?: ?string, fechaSesion: string, horaSesion: string,
     *     duracionMinutos: int, dirigidaA: string, idCategoria?: ?int, modoAsistencia: string,
     * }  $datos
     */
    public function crearSesion(int $idColegio, array $datos, User $instructor): SesionAcademica
    {
        return DB::transaction(function () use ($idColegio, $datos, $instructor): SesionAcademica {
            $sesion = SesionAcademica::create([
                'idColegio'       => $idColegio,
                'idInstructor'    => $instructor->idUsuario,
                'idTipoSesion'    => $datos['idTipoSesion'],
                'modalidad'       => $datos['modalidad'],
                'urlVirtual'      => $datos['modalidad'] === SesionAcademica::MODALIDAD_VIRTUAL ? ($datos['urlVirtual'] ?? null) : null,
                'tema'            => $datos['tema'],
                'descripcion'     => $datos['descripcion'] ?? null,
                'lugar'           => $datos['lugar'] ?? null,
                'fechaSesion'     => $datos['fechaSesion'],
                'horaSesion'      => $datos['horaSesion'],
                'duracionMinutos' => $datos['duracionMinutos'],
                'dirigidaA'       => $datos['dirigidaA'],
                'idCategoria'     => $datos['dirigidaA'] === SesionAcademica::DIRIGIDA_CATEGORIA ? ($datos['idCategoria'] ?? null) : null,
                'modoAsistencia'  => $datos['modoAsistencia'],
                'estadoSesion'    => SesionAcademica::ESTADO_PROGRAMADA,
                'sesionAbierta'   => false,
            ]);

            $arbitros = Arbitro::where('idColegio', $idColegio)
                ->when(
                    $sesion->dirigidaA === SesionAcademica::DIRIGIDA_CATEGORIA,
                    fn ($q) => $q->where('idCategoria', $sesion->idCategoria),
                )
                ->get(['idArbitro']);

            $ahora = now();
            $filas = $arbitros->map(fn (Arbitro $arbitro) => [
                'idColegio'             => $idColegio,
                'idSesion'              => $sesion->idSesion,
                'idArbitro'             => $arbitro->idArbitro,
                'estadoAsistencia'      => AsistenciaAcademica::ESTADO_AUSENTE,
                'registradoPor'         => AsistenciaAcademica::REGISTRADO_SISTEMA,
                'confirmadoInstructor'  => false,
                'created_at'            => $ahora,
                'updated_at'            => $ahora,
            ])->all();

            if (! empty($filas)) {
                AsistenciaAcademica::insert($filas);
            }

            return $sesion->fresh();
        });
    }

    /**
     * @throws \RuntimeException  Si la sesión ya no está programada.
     */
    public function abrirSesion(SesionAcademica $sesion): void
    {
        if ($sesion->estadoSesion !== SesionAcademica::ESTADO_PROGRAMADA) {
            throw new \RuntimeException('Solo se puede abrir una sesión que está programada.');
        }

        $sesion->update([
            'sesionAbierta' => true,
            'estadoSesion'  => SesionAcademica::ESTADO_EN_CURSO,
        ]);
    }

    /**
     * Cierra la sesión y confirma la lista de asistencia como definitiva —
     * es la misma acción: una vez el instructor confirma, no hay una
     * "confirmación" separada de un "cierre" separado.
     *
     * @throws \RuntimeException  Si la sesión no está en curso.
     */
    public function confirmarYCerrarSesion(SesionAcademica $sesion): void
    {
        if ($sesion->estadoSesion !== SesionAcademica::ESTADO_EN_CURSO) {
            throw new \RuntimeException('Solo se puede cerrar una sesión que está en curso.');
        }

        DB::transaction(function () use ($sesion): void {
            $sesion->asistencias()->update(['confirmadoInstructor' => true]);

            $sesion->update([
                'sesionAbierta' => false,
                'estadoSesion'  => SesionAcademica::ESTADO_FINALIZADA,
            ]);
        });

        SesionAcademicaCerradaEvent::dispatch($sesion->fresh());
    }

    /**
     * Edita los datos de una sesión que todavía no se abrió — una vez abierta
     * o finalizada, sus datos quedan fijos (ya hay asistencia real registrada
     * contra ella).
     *
     * @param  array<string, mixed>  $datos  Mismas claves que crearSesion().
     *
     * @throws \RuntimeException  Si la sesión ya no está programada.
     */
    public function actualizarSesion(SesionAcademica $sesion, array $datos): void
    {
        if ($sesion->estadoSesion !== SesionAcademica::ESTADO_PROGRAMADA) {
            throw new \RuntimeException('Solo se puede editar una sesión que aún no se ha abierto.');
        }

        $sesion->update([
            'idTipoSesion'    => $datos['idTipoSesion'],
            'modalidad'       => $datos['modalidad'],
            'urlVirtual'      => $datos['modalidad'] === SesionAcademica::MODALIDAD_VIRTUAL ? ($datos['urlVirtual'] ?? null) : null,
            'tema'            => $datos['tema'],
            'descripcion'     => $datos['descripcion'] ?? null,
            'lugar'           => $datos['lugar'] ?? null,
            'fechaSesion'     => $datos['fechaSesion'],
            'horaSesion'      => $datos['horaSesion'],
            'duracionMinutos' => $datos['duracionMinutos'],
            'modoAsistencia'  => $datos['modoAsistencia'],
        ]);
    }

    /**
     * @throws \RuntimeException  Si la sesión ya está finalizada.
     */
    public function cancelarSesion(SesionAcademica $sesion): void
    {
        if ($sesion->estadoSesion === SesionAcademica::ESTADO_FINALIZADA) {
            throw new \RuntimeException('No se puede cancelar una sesión ya finalizada.');
        }

        $sesion->update([
            'sesionAbierta' => false,
            'estadoSesion'  => SesionAcademica::ESTADO_CANCELADA,
        ]);
    }
}
