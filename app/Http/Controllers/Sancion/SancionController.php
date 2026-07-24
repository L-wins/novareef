<?php

declare(strict_types=1);

namespace App\Http\Controllers\Sancion;

use App\Http\Controllers\Concerns\ResuelveColegio;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sancion\CambiarEstadoSancionRequest;
use App\Http\Requests\Sancion\StoreSancionRequest;
use App\Models\Arbitro;
use App\Models\Sancion;
use App\Models\TipoSancion;
use App\Services\SancionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SancionController extends Controller
{
    use ResuelveColegio;

    public function __construct(
        private readonly SancionService $sanciones,
    ) {}

    public function index(Request $request): View
    {
        $idColegio = $this->idColegioActivo();
        $esArbitro = Auth::user()->rolUsuario === 'arbitro';

        $query = Sancion::where('idColegio', $idColegio)
            ->with(['arbitro.usuario', 'tipo']);

        if ($esArbitro) {
            $arbitro = $this->arbitroAutenticado();
            $query->where('idArbitro', $arbitro->idArbitro);
        } else {
            $query->when($request->filled('idArbitro'), fn ($q) => $q->where('idArbitro', $request->integer('idArbitro')))
                  ->when($request->filled('estado'), fn ($q) => $q->where('estadoSancion', $request->string('estado')));
        }

        $sanciones = $query->orderByDesc('fechaHecho')->paginate(20)->withQueryString();

        $arbitros = $esArbitro ? collect() : Arbitro::where('idColegio', $idColegio)->with('usuario')->get();
        $resumen  = $esArbitro ? null : $this->sanciones->resumenParaDashboard($idColegio, limiteRecientes: 0);

        return view('sanciones.index', compact('sanciones', 'arbitros', 'esArbitro', 'resumen'));
    }

    public function create(): View
    {
        $idColegio = $this->idColegioActivo();

        $arbitros = Arbitro::where('idColegio', $idColegio)->with('usuario')->get();
        $tipos    = TipoSancion::where('idColegio', $idColegio)->where('esActivo', true)->orderBy('etiqueta')->get();

        return view('sanciones.create', compact('arbitros', 'tipos'));
    }

    public function store(StoreSancionRequest $request): RedirectResponse
    {
        try {
            $sancion = $this->sanciones->crearSancion($this->idColegioActivo(), $request->validated(), Auth::user());
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('sanciones.show', $sancion->idSancion)
            ->with('success', 'Sanción registrada correctamente.');
    }

    public function show(int $id): View
    {
        $sancion   = $this->sancionDelColegio($id, ['arbitro.usuario', 'tipo', 'partido', 'usuarioImpuso', 'movimientoFinanciero', 'historial.usuarioAccion']);
        $esArbitro = Auth::user()->rolUsuario === 'arbitro';

        if ($esArbitro) {
            $arbitro = $this->arbitroAutenticado();
            abort_unless((int) $sancion->idArbitro === $arbitro->idArbitro, 403);
        }

        $totalReciente = $this->sanciones->totalSancionesRecientes($sancion);
        $esReincidente = $this->sanciones->esReincidente($sancion);

        return view('sanciones.show', compact('sancion', 'totalReciente', 'esReincidente', 'esArbitro'));
    }

    public function acta(int $id): \Symfony\Component\HttpFoundation\Response
    {
        $sancion = $this->sancionDelColegio($id, ['arbitro.usuario', 'arbitro.categoria', 'tipo', 'usuarioImpuso', 'movimientoFinanciero', 'colegio']);

        if (Auth::user()->rolUsuario === 'arbitro') {
            $arbitro = $this->arbitroAutenticado();
            abort_unless((int) $sancion->idArbitro === $arbitro->idArbitro, 403);
        }

        $pdf = app('dompdf.wrapper');
        $pdf->loadView('pdf.acta-sancion', ['sancion' => $sancion, 'generadoPor' => Auth::user()]);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download("resolucion-sancion-{$sancion->idSancion}.pdf");
    }

    public function cambiarEstado(CambiarEstadoSancionRequest $request, int $id): RedirectResponse
    {
        $sancion = $this->sancionDelColegio($id);
        $datos   = $request->validated();
        $usuario = Auth::user();

        match ($datos['accion']) {
            'cumplir'             => abort_unless($usuario->can('crear-sanciones'), 403, 'No tienes permiso para marcar sanciones como cumplidas.'),
            'anular', 'resolver'  => abort_unless($usuario->can('editar-sanciones'), 403, 'No tienes permiso para anular o resolver apelaciones.'),
            'apelar'              => $this->autorizarApelacion($sancion, $usuario),
        };

        try {
            match ($datos['accion']) {
                'cumplir'  => $this->sanciones->cumplir($sancion, $usuario, $datos['motivo'] ?? null),
                'anular'   => $this->sanciones->anular($sancion, $usuario, $datos['motivo']),
                'apelar'   => $this->sanciones->apelar($sancion, $usuario, $datos['motivo']),
                'resolver' => $this->sanciones->resolverApelacion($sancion, $datos['resultado'], $usuario, $datos['motivo'] ?? null),
            };
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $mensajes = [
            'cumplir'  => 'Sanción marcada como cumplida.',
            'anular'   => 'Sanción anulada.',
            'apelar'   => 'Tu apelación fue registrada. El comité la revisará.',
            'resolver' => 'Apelación resuelta.',
        ];

        return redirect()
            ->route('sanciones.show', $sancion->idSancion)
            ->with('success', $mensajes[$datos['accion']]);
    }

    /**
     * Apelar es un acto exclusivo del árbitro sancionado — el Comité NUNCA
     * apela "en nombre de" alguien. Si el Comité quiere dejar sin efecto una
     * sanción por su propia iniciativa (ej. reconoce un error de registro),
     * ya existe anular() con permission:editar-sanciones, que logra el mismo
     * efecto sin fingir una apelación que el árbitro nunca presentó. Por eso
     * esta acción no depende de ningún permission de Spatie, solo de que
     * quien la ejecuta sea el árbitro dueño de la sanción.
     */
    private function autorizarApelacion(Sancion $sancion, $usuario): void
    {
        abort_unless($usuario->rolUsuario === 'arbitro', 403, 'Solo el árbitro sancionado puede apelar su propia sanción.');

        $arbitro = $this->arbitroAutenticado();
        abort_unless((int) $sancion->idArbitro === $arbitro->idArbitro, 403, 'No puedes apelar una sanción que no es tuya.');
    }

    private function sancionDelColegio(int $id, array $relaciones = []): Sancion
    {
        $sancion = Sancion::with($relaciones)->findOrFail($id);

        abort_unless((int) $sancion->idColegio === $this->idColegioActivo(), 403);

        return $sancion;
    }
}
