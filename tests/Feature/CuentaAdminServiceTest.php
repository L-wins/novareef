<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Services\CuentaAdminService;
use App\Services\LimiteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\TestCase;

class CuentaAdminServiceTest extends TestCase
{
    use RefreshDatabase;
    use CreaColegioDePrueba;

    private CuentaAdminService $cuentasAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cuentasAdmin = app(CuentaAdminService::class);

        foreach (['ejecutivo', 'tesorero', 'designador', 'sanciones', 'tecnico', 'veedor'] as $rol) {
            Role::create(['name' => $rol, 'guard_name' => 'web']);
        }

        Mail::fake();
    }

    public function test_crea_cuenta_sin_email_propio_y_notifica_al_correo_del_colegio(): void
    {
        $colegio = $this->crearColegio($this->crearPlan(['limiteCuentasAdmin' => 5]));

        $usuario = $this->cuentasAdmin->crear(
            idColegio:                $colegio->idColegio,
            nombreColegio:            $colegio->nombreColegio,
            urlAcceso:                'http://localhost/login',
            nombre:                   'Cuenta de Prueba',
            username:                 'cuenta_prueba',
            email:                    null,
            emailColegioNotificacion: $colegio->emailColegio,
            rol:                      'designador',
        );

        $this->assertNull($usuario->fresh()->emailUsuario);
        $this->assertSame('cuenta_prueba', $usuario->fresh()->usernameUsuario);
        $this->assertTrue($usuario->fresh()->must_change_password);
        $this->assertTrue($usuario->hasRole('designador'));

        Mail::assertSent(\App\Mail\CredencialesColegioMail::class, function ($mail) use ($colegio) {
            return $mail->hasTo($colegio->emailColegio);
        });
    }

    public function test_no_deja_crear_cuenta_si_ya_se_alcanzo_el_limite(): void
    {
        $colegio = $this->crearColegio($this->crearPlan(['limiteCuentasAdmin' => 1]));
        $this->crearCuentaAdmin($colegio, 'ejecutivo');

        $this->expectException(\RuntimeException::class);

        $this->cuentasAdmin->crear(
            idColegio:                $colegio->idColegio,
            nombreColegio:            $colegio->nombreColegio,
            urlAcceso:                'http://localhost/login',
            nombre:                   'Otra cuenta',
            username:                 'otra_cuenta',
            email:                    null,
            emailColegioNotificacion: $colegio->emailColegio,
            rol:                      'designador',
        );
    }

    public function test_revocar_bloquea_el_login_sin_borrar_el_registro(): void
    {
        $colegio = $this->crearColegio($this->crearPlan());
        $cuenta  = $this->crearCuentaAdmin($colegio, 'tesorero');

        $this->cuentasAdmin->revocar($cuenta);

        $this->assertSame('inactivo', $cuenta->fresh()->estadoUsuario);
        $this->assertNotNull(User::find($cuenta->idUsuario));
    }

    public function test_reactivar_respeta_el_limite_del_plan(): void
    {
        $colegio = $this->crearColegio($this->crearPlan(['limiteCuentasAdmin' => 1]));
        $revocada = $this->crearCuentaAdmin($colegio, 'tesorero', 'inactivo');
        $this->crearCuentaAdmin($colegio, 'ejecutivo'); // ocupa el único cupo disponible

        $this->expectException(\RuntimeException::class);
        $this->cuentasAdmin->reactivar($revocada);
    }

    public function test_reactivar_funciona_cuando_hay_cupo(): void
    {
        $colegio  = $this->crearColegio($this->crearPlan(['limiteCuentasAdmin' => 2]));
        $revocada = $this->crearCuentaAdmin($colegio, 'tesorero', 'inactivo');

        $this->cuentasAdmin->reactivar($revocada);

        $this->assertSame('activo', $revocada->fresh()->estadoUsuario);
    }

    public function test_actualizar_sincroniza_el_rol_de_spatie(): void
    {
        $colegio = $this->crearColegio($this->crearPlan());
        $cuenta  = $this->crearCuentaAdmin($colegio, 'designador');
        $cuenta->assignRole('designador');

        $this->cuentasAdmin->actualizar($cuenta, ['nombreUsuario' => 'Nuevo Nombre', 'rolUsuario' => 'tesorero']);

        $cuenta->refresh();
        $this->assertSame('Nuevo Nombre', $cuenta->nombreUsuario);
        $this->assertSame('tesorero', $cuenta->rolUsuario);
        $this->assertTrue($cuenta->hasRole('tesorero'));
        $this->assertFalse($cuenta->hasRole('designador'));
    }
}
