<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Arbitro;
use App\Models\DocumentoArbitro;
use App\Models\RequisitoDocumentoArbitro;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class DocumentoArbitroService
{
    public const DISCO = 'local';

    public const MAX_KB = 10240;

    public const MIME_RULE = 'pdf,doc,docx,jpg,jpeg,png';

    private const DIRECTORIO_PLANTILLAS = 'arbitros/plantillas';

    private const DIRECTORIO_ENTREGAS = 'arbitros/entregas';

    /**
     * @return Collection<int, RequisitoDocumentoArbitro>
     */
    public function requisitosParaColegio(
        int $idColegio,
        bool $soloActivos = true,
        ?int $idCategoria = null,
        ?int $idArbitro = null,
    ): Collection {
        return RequisitoDocumentoArbitro::query()
            ->with('categoria')
            ->where('idColegio', $idColegio)
            ->when($soloActivos, fn ($query) => $query->where('activo', true))
            ->when(
                $idCategoria !== null || $idArbitro !== null,
                fn ($query) => $query->where(fn ($scoped) => $scoped
                    ->where(fn ($todos) => $todos->whereNull('idCategoria')->whereNull('idArbitro'))
                    ->when($idCategoria !== null, fn ($q) => $q->orWhere('idCategoria', $idCategoria))
                    ->when($idArbitro !== null, fn ($q) => $q->orWhere('idArbitro', $idArbitro))),
            )
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();
    }

    /**
     * @return Collection<int, array{requisito: RequisitoDocumentoArbitro, documento: ?DocumentoArbitro, historial: Collection<int, DocumentoArbitro>}>
     */
    public function panelParaArbitro(Arbitro $arbitro): Collection
    {
        $requisitos = $this->requisitosParaColegio(
            (int) $arbitro->idColegio,
            idCategoria: $arbitro->idCategoria ? (int) $arbitro->idCategoria : null,
            idArbitro: (int) $arbitro->idArbitro,
        );
        $documentos = $arbitro->relationLoaded('documentos')
            ? $arbitro->documentos
            : $arbitro->documentos()->with(['requisito', 'revisor'])->get();

        return $requisitos->map(function (RequisitoDocumentoArbitro $requisito) use ($documentos): array {
            $historial = $documentos
                ->where('idRequisito', $requisito->idRequisito)
                ->sortByDesc('idDocumento')
                ->values();

            return [
                'requisito' => $requisito,
                'documento' => $historial->first(),
                'historial' => $historial,
            ];
        });
    }

    /**
     * @param  Collection<int, array{requisito: RequisitoDocumentoArbitro, documento: ?DocumentoArbitro, historial: Collection<int, DocumentoArbitro>}>|null  $items
     * @return array{total: int, obligatorios: int, entregados: int, aprobadosObligatorios: int, pendientesRevision: int, devueltos: int, completo: bool, porcentaje: int}
     */
    public function resumenParaArbitro(Arbitro $arbitro, ?Collection $items = null): array
    {
        $items ??= $this->panelParaArbitro($arbitro);

        $obligatorios = $items->filter(fn (array $item): bool => (bool) $item['requisito']->obligatorio);
        $entregados = $items->filter(fn (array $item): bool => $item['documento'] !== null);
        $aprobadosObligatorios = $obligatorios->filter(
            fn (array $item): bool => $item['documento']?->estadoRevision === DocumentoArbitro::ESTADO_APROBADO,
        )->count();
        $pendientesRevision = $items->filter(
            fn (array $item): bool => $item['documento']?->estadoRevision === DocumentoArbitro::ESTADO_EN_REVISION,
        )->count();
        $devueltos = $items->filter(
            fn (array $item): bool => $item['documento']?->estadoRevision === DocumentoArbitro::ESTADO_DEVUELTO,
        )->count();

        $obligatoriosTotal = $obligatorios->count();

        return [
            'total' => $items->count(),
            'obligatorios' => $obligatoriosTotal,
            'entregados' => $entregados->count(),
            'aprobadosObligatorios' => $aprobadosObligatorios,
            'pendientesRevision' => $pendientesRevision,
            'devueltos' => $devueltos,
            'completo' => $obligatoriosTotal === 0 || $aprobadosObligatorios === $obligatoriosTotal,
            'porcentaje' => $obligatoriosTotal === 0
                ? 100
                : (int) round(($aprobadosObligatorios / $obligatoriosTotal) * 100),
        ];
    }

    public function guardarPlantilla(RequisitoDocumentoArbitro $requisito, UploadedFile $archivo): void
    {
        if ($requisito->plantillaRuta) {
            Storage::disk(self::DISCO)->delete($requisito->plantillaRuta);
        }

        $ruta = $archivo->store(self::DIRECTORIO_PLANTILLAS.'/'.$requisito->idColegio, self::DISCO);

        $requisito->forceFill([
            'plantillaRuta' => $ruta,
            'plantillaNombreOriginal' => $archivo->getClientOriginalName(),
            'plantillaMime' => $archivo->getMimeType(),
            'plantillaTamanoBytes' => $archivo->getSize(),
        ])->save();
    }

    public function guardarEntrega(
        Arbitro $arbitro,
        RequisitoDocumentoArbitro $requisito,
        UploadedFile $archivo,
    ): DocumentoArbitro {
        if ((int) $arbitro->idColegio !== (int) $requisito->idColegio || ! $requisito->activo) {
            throw new RuntimeException('El requisito documental no pertenece al colegio del árbitro o no está activo.');
        }

        if ($requisito->idCategoria !== null && (int) $requisito->idCategoria !== (int) $arbitro->idCategoria) {
            throw new RuntimeException('Este documento no aplica para la categoría del árbitro.');
        }

        if ($requisito->idArbitro !== null && (int) $requisito->idArbitro !== (int) $arbitro->idArbitro) {
            throw new RuntimeException('Este documento no aplica para este árbitro.');
        }

        $version = ((int) DocumentoArbitro::query()
            ->where('idArbitro', $arbitro->idArbitro)
            ->where('idRequisito', $requisito->idRequisito)
            ->max('version')) + 1;

        $ruta = $archivo->store(
            self::DIRECTORIO_ENTREGAS.'/'.$arbitro->idColegio.'/'.$arbitro->idArbitro,
            self::DISCO,
        );

        return DocumentoArbitro::create([
            'idArbitro' => $arbitro->idArbitro,
            'idRequisito' => $requisito->idRequisito,
            'nombreDocumento' => $requisito->nombre,
            'nombreOriginal' => $archivo->getClientOriginalName(),
            'archivoRuta' => $ruta,
            'tipoMime' => $archivo->getMimeType(),
            'tamanoBytes' => $archivo->getSize(),
            'obligatorio' => $requisito->obligatorio,
            'fechaSubida' => now(),
            'estadoRevision' => $requisito->requiereRevision
                ? DocumentoArbitro::ESTADO_EN_REVISION
                : DocumentoArbitro::ESTADO_APROBADO,
            'version' => $version,
        ]);
    }

    public function aprobar(DocumentoArbitro $documento, User $revisor): void
    {
        $documento->update([
            'estadoRevision' => DocumentoArbitro::ESTADO_APROBADO,
            'comentarioRevision' => null,
            'fechaRevision' => now(),
            'idUsuarioRevision' => $revisor->idUsuario,
        ]);
    }

    public function devolver(DocumentoArbitro $documento, string $comentario, User $revisor): void
    {
        $documento->update([
            'estadoRevision' => DocumentoArbitro::ESTADO_DEVUELTO,
            'comentarioRevision' => $comentario,
            'fechaRevision' => now(),
            'idUsuarioRevision' => $revisor->idUsuario,
        ]);
    }

    /**
     * Elimina un requisito documental si ningún árbitro ha entregado nada
     * contra él todavía — mismo criterio que CategoriaArbitroService::eliminar()
     * (no se borra un catálogo con historial real enganchado). Con entregas
     * existentes, la vía es pausarlo (activo=false), nunca eliminarlo.
     *
     * @throws \RuntimeException Si el requisito ya tiene entregas registradas.
     */
    public function eliminarRequisito(RequisitoDocumentoArbitro $requisito): void
    {
        if ($requisito->documentos()->exists()) {
            throw new RuntimeException('No se puede eliminar un requisito con documentos entregados — pausalo en su lugar.');
        }

        if ($requisito->plantillaRuta) {
            Storage::disk(self::DISCO)->delete($requisito->plantillaRuta);
        }

        $requisito->delete();
    }
}
