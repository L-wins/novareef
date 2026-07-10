<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\AdminAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonacionController extends Controller
{
    public function __construct(private readonly AdminAuditService $auditoria) {}

    /**
     * Termina la impersonación y vuelve al panel admin. Solo cierra la
     * sesión del guard 'web' (el usuario impersonado) — la sesión del
     * guard 'admin' sigue viva en paralelo, no hace falta volver a loguearse.
     */
    public function salir(Request $request): RedirectResponse
    {
        $idColegio = $request->session()->get('impersonacion.idColegio');
        $idAdmin   = $request->session()->get('impersonacion.idAdmin');

        Auth::guard('web')->logout();

        $request->session()->forget(['impersonacion.idAdmin', 'impersonacion.idColegio', 'impersonacion.expira']);

        if ($idAdmin && $admin = \App\Models\Admin::find($idAdmin)) {
            $this->auditoria->registrar($admin, 'impersonar_salir', 'colegio', $idColegio, 'Salió de la impersonación.');
        }

        return $idColegio
            ? redirect()->route('admin.colegios.show', $idColegio)
            : redirect()->route('admin.dashboard');
    }
}
