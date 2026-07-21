<?php

namespace Tests;

use App\Services\PoliticaPrivacidadService;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * ExigirAceptacionPolitica (Habeas Data) redirige a cualquier usuario
     * del guard 'web' que no haya aceptado la política vigente — cientos de
     * tests existentes autentican con actingAs() sin pasar por ese flujo,
     * porque no es lo que están probando. Se les da la aceptación por
     * sentada acá para no tener que tocar cada test uno por uno. Los tests
     * que sí prueban el gate en sí (PoliticaPrivacidadTest) usan
     * actingAsSinAceptarPolitica() para evitar este atajo.
     */
    public function actingAs(UserContract $user, $guard = null)
    {
        if (($guard === null || $guard === 'web') && $user instanceof \App\Models\User) {
            app(PoliticaPrivacidadService::class)->registrarAceptacionGeneral($user, '127.0.0.1');
        }

        return parent::actingAs($user, $guard);
    }

    protected function actingAsSinAceptarPolitica(UserContract $user, $guard = null): static
    {
        return parent::actingAs($user, $guard);
    }
}
