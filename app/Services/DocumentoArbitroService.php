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
    public function requisitosParaColegio(int $idColegio, bool $soloActivos = true): Collection
    {
        return RequisitoDocumentoArbitro::query()
            ->where('idColegio', $idColegio)
            ->when($soloActivos, fn ($query) => $query->where('activo', true))
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();
    }

    /**
     * @return Collection<int, array{requisito: RequisitoDocumentoArbitro, documento: ?DocumentoArbitro, historial: Collection<int, DocumentoArbitro>}>
     */
    public function panelParaArbitro(Arbitro $arbitro): Collection
    {
        $requisitos = $this->requisitosParaColegio((int) $arbitro->idColegio);
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
            throw new RuntimeException('El requisito documental no pertenece al colegio del arbitro o no esta activo.');
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
}
