<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Solo existe (y va a existir) un único superadmin — sin roles ni permisos
 * granulares que gestionar. Cualquier cuenta admin autenticada y activa
 * tiene acceso completo al panel.
 */
class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    private function crearAdmin(): Admin
    {
        return Admin::create([
            'nombre'   => 'Super',
            'email'    => 'super@test.com',
            'password' => Hash::make('password'),
            'activo'   => true,
        ]);
    }

    public function test_un_admin_autenticado_accede_a_todo_el_panel_sin_permisos_especiales(): void
    {
        $admin = $this->crearAdmin();

        $this->actingAs($admin, 'admin')->get('/novareef-panel')->assertOk();
        $this->actingAs($admin, 'admin')->get('/novareef-panel/colegios')->assertOk();
        $this->actingAs($admin, 'admin')->get('/novareef-panel/colegios/crear')->assertOk();
        $this->actingAs($admin, 'admin')->get('/novareef-panel/planes')->assertOk();
        $this->actingAs($admin, 'admin')->get('/novareef-panel/suscripciones')->assertOk();
        $this->actingAs($admin, 'admin')->get('/novareef-panel/usuarios')->assertOk();
        $this->actingAs($admin, 'admin')->get('/novareef-panel/logs')->assertOk();
    }

    public function test_un_admin_desactivado_a_mitad_de_sesion_pierde_acceso(): void
    {
        $admin = $this->crearAdmin();

        $this->actingAs($admin, 'admin')->get('/novareef-panel')->assertOk();

        $admin->update(['activo' => false]);

        $this->actingAs($admin, 'admin')->get('/novareef-panel')->assertRedirect(route('admin.login'));
    }
}
