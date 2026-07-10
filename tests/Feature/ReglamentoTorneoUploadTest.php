<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ReglamentoTorneo;
use App\Models\Torneo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\TestCase;

/**
 * Límite real de subida del reglamento en PDF: 20 MB (antes era 60 MB).
 */
class ReglamentoTorneoUploadTest extends TestCase
{
    use RefreshDatabase;
    use CreaColegioDePrueba;

    private function crearUsuarioYTorneo(): array
    {
        Permission::create(['name' => 'ver-torneos', 'guard_name' => 'web']);
        Permission::create(['name' => 'editar-torneos', 'guard_name' => 'web']);

        $colegio = $this->crearColegio($this->crearPlan());
        $usuario = User::factory()->create([
            'idColegio'            => $colegio->idColegio,
            'rolUsuario'           => 'ejecutivo',
            'must_change_password' => false,
        ]);
        $usuario->givePermissionTo(['ver-torneos', 'editar-torneos']);

        $torneo = Torneo::create([
            'idColegio'         => $colegio->idColegio,
            'idUsuarioCreador'  => $usuario->idUsuario,
            'organizadorNombre' => 'Organizador de prueba',
            'nombreTorneo'      => 'Torneo de prueba',
            'temporada'         => 2026,
            'fechaInicio'       => today(),
            'fechaFin'          => today()->addMonths(3),
        ]);

        return [$usuario, $torneo];
    }

    public function test_rechaza_un_pdf_de_mas_de_20mb(): void
    {
        Storage::fake('public');
        [$usuario, $torneo] = $this->crearUsuarioYTorneo();

        $archivo = UploadedFile::fake()->create('reglamento.pdf', 21 * 1024, 'application/pdf');

        $response = $this->actingAs($usuario)
            ->post(route('torneos.perfil.guardar', $torneo->idTorneo), ['reglamentoPDF' => $archivo]);

        $response->assertSessionHasErrors('reglamentoPDF');
        $this->assertSame(0, ReglamentoTorneo::where('idTorneo', $torneo->idTorneo)->count());
    }

    public function test_acepta_un_pdf_de_menos_de_20mb(): void
    {
        Storage::fake('public');
        [$usuario, $torneo] = $this->crearUsuarioYTorneo();

        $archivo = UploadedFile::fake()->create('reglamento.pdf', 5 * 1024, 'application/pdf');

        $response = $this->actingAs($usuario)
            ->post(route('torneos.perfil.guardar', $torneo->idTorneo), ['reglamentoPDF' => $archivo]);

        $response->assertSessionDoesntHaveErrors('reglamentoPDF');
        $this->assertSame(1, ReglamentoTorneo::where('idTorneo', $torneo->idTorneo)->count());
    }
}
