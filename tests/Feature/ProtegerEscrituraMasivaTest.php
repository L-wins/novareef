<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\SesionAcademica;
use App\Models\TipoSesionAcademica;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\TestCase;

/**
 * Verifica ProtegerEscrituraMasiva con tráfico HTTP real (no bypaseando el
 * middleware) — parte de la auditoría de carga: confirma que el límite por
 * defecto (30 escrituras/60s) protege sin necesitar tocar producción, y que
 * un uso intensivo pero legítimo (un designador armando varios partidos
 * seguidos de una jornada) puede rozarlo sin ser abuso.
 */
class ProtegerEscrituraMasivaTest extends TestCase
{
    use CreaColegioDePrueba;
    use RefreshDatabase;

    public function test_bloquea_al_superar_el_limite_por_defecto(): void
    {
        $colegio = $this->crearColegio();
        $usuario = User::factory()->create(['idColegio' => $colegio->idColegio, 'rolUsuario' => 'ejecutivo']);
        $this->actingAs($usuario, 'web');

        $respuestas = [];
        for ($i = 0; $i < 35; $i++) {
            // patchJson (no patch): envía Accept: application/json, igual que
            // theme.js real (resources/js/shared/theme.js) — sin ese header
            // el middleware no puede distinguir una llamada AJAX de una
            // navegación normal y responde con redirect 302 en vez de 429
            // JSON. Confirmado en vivo: una primera versión de este test sin
            // el header pasaba las 35 llamadas con status 200/302 sin nunca
            // ver un 429, no porque el middleware no bloqueara (si bloqueaba,
            // en la llamada 31 exacta) sino porque el test no pedía JSON.
            $respuestas[] = $this->patchJson(route('preferencias.tema'), ['tema' => $i % 2 === 0 ? 'dark' : 'light']);
        }

        $exitosas = collect($respuestas)->filter(fn ($r) => $r->status() !== 429)->count();
        $bloqueadas = collect($respuestas)->filter(fn ($r) => $r->status() === 429)->count();

        $this->assertSame(30, $exitosas, 'Deben pasar exactamente las primeras 30 escrituras (MAX_ESCRITURAS del middleware).');
        $this->assertSame(5, $bloqueadas, 'Las siguientes 5 deben ser bloqueadas con 429.');

        $ultima = end($respuestas);
        $ultima->assertStatus(429);
        $ultima->assertJsonPath('success', false);
    }

    public function test_override_de_academico_permite_escritura_masiva_legitima(): void
    {
        $colegio = $this->crearColegio($this->crearPlan(['modulosJSON' => ['arbitros', 'torneos', 'designaciones', 'academico']]));
        $instructor = User::factory()->create(['idColegio' => $colegio->idColegio, 'rolUsuario' => 'tecnico']);

        $tipo = TipoSesionAcademica::create(['idColegio' => $colegio->idColegio, 'etiqueta' => 'Charla', 'esActivo' => true]);
        $sesion = SesionAcademica::create([
            'idColegio' => $colegio->idColegio,
            'idInstructor' => $instructor->idUsuario,
            'idTipoSesion' => $tipo->idTipoSesion,
            'modalidad' => 'presencial',
            'tema' => 'Sesión de prueba',
            'fechaSesion' => today(),
            'horaSesion' => '18:00',
            'duracionMinutos' => 60,
            'dirigidaA' => 'todos',
            'modoAsistencia' => 'scanner',
            'esObligatoria' => true,
            'estadoSesion' => SesionAcademica::ESTADO_EN_CURSO,
            'sesionAbierta' => true,
        ]);

        // 40 árbitros escaneando su carnet en la misma sesión — más que el
        // límite general (30/60s) pero muy por debajo del override real
        // (500/60s en academico.scanner) que existe justamente para este caso.
        $arbitros = [];
        for ($i = 0; $i < 40; $i++) {
            $arbitros[] = $this->crearArbitro($colegio);
        }

        $this->actingAs($instructor, 'web');

        $bloqueados = 0;
        foreach ($arbitros as $arbitro) {
            $respuesta = $this->postJson(route('academico.scanner'), [
                'idSesion' => $sesion->idSesion,
                'codigoCarnet' => $arbitro->codigoCarnet,
            ]);

            if ($respuesta->status() === 429) {
                $bloqueados++;
            }
        }

        $this->assertSame(
            0,
            $bloqueados,
            '40 escaneos en la misma sesión no deben chocar con el límite general de 30/60s — academico.scanner tiene override a 500/60s para este caso real.'
        );
    }

    public function test_usuarios_distintos_tienen_limites_independientes(): void
    {
        $colegio = $this->crearColegio();
        $usuarioA = User::factory()->create(['idColegio' => $colegio->idColegio, 'rolUsuario' => 'ejecutivo']);
        $usuarioB = User::factory()->create(['idColegio' => $colegio->idColegio, 'rolUsuario' => 'ejecutivo']);

        $this->actingAs($usuarioA, 'web');
        for ($i = 0; $i < 30; $i++) {
            $this->patchJson(route('preferencias.tema'), ['tema' => 'dark']);
        }

        // A ya agotó su cupo — B, otro usuario, no debe verse afectado.
        $respuestaB = $this->actingAs($usuarioB, 'web')->patchJson(route('preferencias.tema'), ['tema' => 'dark']);

        $respuestaB->assertStatus(200);
    }
}
