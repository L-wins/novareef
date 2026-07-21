<?php

declare(strict_types=1);

namespace App\Http\Controllers\Arbitro;

use App\Http\Controllers\Concerns\ResuelveColegio;
use App\Http\Controllers\Controller;
use App\Http\Requests\Arbitro\MiPerfilRequest;
use App\Services\ArbitroService;
use App\Services\PoliticaPrivacidadService;
use App\Services\ReporteFinanzasService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ArbitroPerfilController extends Controller
{
    use ResuelveColegio;

    public function __construct(
        private readonly ArbitroService $arbitros,
        private readonly ReporteFinanzasService $reportes,
        private readonly PoliticaPrivacidadService $politica,
    ) {}

    public function show(): View
    {
        $arbitro = $this->arbitroAutenticado(['usuario', 'categoria', 'colegio', 'estado', 'documentos']);

        return view('arbitros.mi-perfil', [
            'arbitro'                => $arbitro,
            'saldoPendienteCobrar'   => $this->reportes->saldoPendienteArbitro($arbitro),
            'yaAceptoDatosSensibles' => $this->politica->haAceptadoDatosSensibles($arbitro->usuario),
        ]);
    }

    public function update(MiPerfilRequest $request): RedirectResponse
    {
        $arbitro = $this->arbitroAutenticado(['usuario']);

        $this->arbitros->actualizarPerfilPropio($arbitro, $request->validated());
        $this->registrarConsentimientoDatosSensiblesSiAplica($request, $arbitro->usuario);

        return redirect()->route('arbitros.mi-perfil')->with('success', 'Perfil actualizado correctamente.');
    }

    public function completar(): View
    {
        return view('arbitros.completar-perfil', [
            'arbitro' => $this->arbitroAutenticado(['usuario', 'categoria']),
        ]);
    }

    public function guardar(MiPerfilRequest $request): RedirectResponse
    {
        $arbitro = $this->arbitroAutenticado(['usuario']);

        $this->arbitros->actualizarPerfilPropio($arbitro, $request->validated());
        $this->registrarConsentimientoDatosSensiblesSiAplica($request, $arbitro->usuario);

        return redirect()->route('dashboard')->with('success', 'Perfil completado correctamente. ¡Bienvenido a NovaReef!');
    }

    private function registrarConsentimientoDatosSensiblesSiAplica(Request $request, \App\Models\User $usuario): void
    {
        if ($request->boolean('consentimientoDatosSensibles')) {
            $this->politica->registrarAceptacionDatosSensibles($usuario, $request->ip());
        }
    }
}
