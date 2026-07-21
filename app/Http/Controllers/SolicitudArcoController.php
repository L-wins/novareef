<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreSolicitudArcoRequest;
use App\Mail\SolicitudArcoMail;
use App\Models\SolicitudArco;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * Canal para ejercer los derechos ARCO (Acceso, Rectificación, Cancelación,
 * Oposición — Ley 1581 de 2012, art. 8). El colegio es quien opera la
 * relación con el árbitro, así que la solicitud se dirige a sus cuentas
 * ejecutivo, no al superadmin de NovaReef.
 */
class SolicitudArcoController extends Controller
{
    public function create(): View
    {
        return view('privacidad.solicitud-arco', ['tipos' => SolicitudArco::TIPOS]);
    }

    public function store(StoreSolicitudArcoRequest $request): RedirectResponse
    {
        $usuario = Auth::user();

        if ($usuario->idColegio === null) {
            return back()->with('error', 'Tu cuenta no está asociada a un colegio.');
        }

        $solicitud = SolicitudArco::create([
            'idUsuario' => $usuario->idUsuario,
            'idColegio' => $usuario->idColegio,
            'tipo'      => $request->string('tipo')->toString(),
            'mensaje'   => $request->string('mensaje')->toString(),
        ]);

        $ejecutivos = User::where('idColegio', $usuario->idColegio)
            ->where('rolUsuario', 'ejecutivo')
            ->get();

        foreach ($ejecutivos as $ejecutivo) {
            Mail::to($ejecutivo->emailUsuario)->send(new SolicitudArcoMail($solicitud));
        }

        return redirect()->route('privacidad.politica')
            ->with('success', 'Tu solicitud fue registrada. El colegio debe atenderla según los plazos de la ley.');
    }
}
