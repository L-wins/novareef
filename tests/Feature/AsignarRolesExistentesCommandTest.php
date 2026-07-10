<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Regresión de dos bugs reales encontrados en este comando:
 *  1. chunkById(200, 'idUsuario', $callback) tenía los argumentos en el orden
 *     equivocado (la firma real es chunkById($count, $callback, $column)) —
 *     el comando lanzaba un TypeError y nunca había podido ejecutarse.
 *  2. ROLES_VALIDOS no incluía 'veedor' (agregado después en una migración
 *     posterior) — esos usuarios quedaban invisibles para el comando para siempre.
 */
class AsignarRolesExistentesCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['arbitro', 'ejecutivo', 'tesorero', 'designador', 'sanciones', 'tecnico', 'veedor'] as $rol) {
            Role::create(['name' => $rol, 'guard_name' => 'web']);
        }
    }

    public function test_el_comando_corre_sin_error_y_asigna_roles(): void
    {
        User::factory()->create(['rolUsuario' => 'arbitro']);
        User::factory()->create(['rolUsuario' => 'ejecutivo']);
        User::factory()->create(['rolUsuario' => 'veedor']);

        $exitCode = $this->artisan('novareef:asignar-roles')->run();

        $this->assertSame(0, $exitCode, 'El comando no debe fallar (regresión del bug de chunkById).');

        foreach (User::all() as $usuario) {
            $this->assertTrue(
                $usuario->hasRole($usuario->rolUsuario),
                "El usuario con rolUsuario={$usuario->rolUsuario} debería tener ese rol de Spatie asignado."
            );
        }
    }

    public function test_es_idempotente_no_reasigna_lo_que_ya_esta_asignado(): void
    {
        $usuario = User::factory()->create(['rolUsuario' => 'designador']);
        $usuario->assignRole('designador');

        $this->artisan('novareef:asignar-roles')->run();

        // No debe fallar ni duplicar el rol al correr sobre alguien que ya lo tenía.
        $this->assertCount(1, $usuario->fresh()->roles);
    }

    public function test_no_toca_usuarios_con_rol_no_reconocido(): void
    {
        $usuario = User::factory()->create(['rolUsuario' => 'superadmin']);

        $this->artisan('novareef:asignar-roles')->run();

        $this->assertCount(0, $usuario->fresh()->roles);
    }
}
