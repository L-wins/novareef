<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ReglamentoTorneo;
use App\Models\Torneo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class TorneoService
{
    /**
     * Cambia el estado del torneo.
     *
     * @throws \RuntimeException  Si el torneo ya está en ese estado.
     */
    public function cambiarEstado(Torneo $torneo, string $estadoNuevo): void
    {
        if ($torneo->estadoTorneo === $estadoNuevo) {
            throw new \RuntimeException('El torneo ya está en ese estado.');
        }

        $torneo->update(['estadoTorneo' => $estadoNuevo]);
    }

    /**
     * Sube una nueva versión del reglamento del torneo: desactiva la versión
     * actual (si existe) y registra la nueva como vigente.
     */
    public function subirReglamento(Torneo $torneo, UploadedFile $archivo, int $idUsuarioAccion): void
    {
        DB::transaction(function () use ($torneo, $archivo, $idUsuarioAccion): void {
            ReglamentoTorneo::where('idTorneo', $torneo->idTorneo)
                ->where('esActual', true)
                ->update(['esActual' => false]);

            $ruta = $archivo->store('reglamentos', 'public');

            ReglamentoTorneo::create([
                'idTorneo'        => $torneo->idTorneo,
                'nombreArchivo'   => $archivo->getClientOriginalName(),
                'rutaArchivo'     => $ruta,
                'tamanoBytes'     => (int) $archivo->getSize(),
                'esActual'        => true,
                'idUsuarioSubida' => $idUsuarioAccion,
            ]);
        });
    }

    /**
     * Elimina una versión del reglamento (archivo + registro). Si era la
     * versión vigente, promueve automáticamente la más reciente restante.
     */
    public function eliminarReglamento(ReglamentoTorneo $reglamento): void
    {
        $eraActual = $reglamento->esActual;
        $idTorneo  = $reglamento->idTorneo;

        Storage::disk('public')->delete($reglamento->rutaArchivo);
        $reglamento->delete();

        if ($eraActual) {
            ReglamentoTorneo::where('idTorneo', $idTorneo)
                ->latest('created_at')
                ->first()
                ?->update(['esActual' => true]);
        }
    }
}
