<?php

declare(strict_types=1);

namespace Tests\Browser;

use App\Models\Colegio;
use App\Models\DivisionTorneo;
use App\Models\Partido;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use PhpOffice\PhpWord\PhpWord;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\DuskTestCase;

/**
 * El bug real que motivó esta suite: el botón "Confirmar importación"
 * quedó roto porque su JS buscaba la clase .importar-fila, que ya no
 * existía tras el rediseño a tarjetas (.importar-card) — SIEMPRE decía "no
 * hay ningún partido marcado", sin importar cuántos estuvieran marcados.
 * Ningún test HTTP lo detectó porque esos postean directo a la ruta de
 * confirmar, sin pasar por el botón ni por el modal de SweetAlert2. Solo un
 * navegador real, haciendo clic de verdad, puede atrapar esta clase de bug.
 */
class ImportarDesignacionesFlujoTest extends DuskTestCase
{
    use DatabaseTruncation;
    use CreaColegioDePrueba;

    private function crearDesignadorConPermisos(Colegio $colegio): User
    {
        foreach (['ver-designaciones', 'crear-designaciones'] as $permiso) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
        }

        $rol = Role::firstOrCreate(['name' => 'designador', 'guard_name' => 'web']);
        $rol->syncPermissions(['ver-designaciones', 'crear-designaciones']);

        $usuario = User::factory()->create(['idColegio' => $colegio->idColegio, 'rolUsuario' => 'designador']);
        $usuario->assignRole('designador');

        // loginAs() abre una sesión real que sí pasa por ExigirAceptacionPolitica.
        app(\App\Services\PoliticaPrivacidadService::class)->registrarAceptacionGeneral($usuario, '127.0.0.1');

        return $usuario;
    }

    /** Un solo partido, ubicable/matcheable — mismo shape que el fixture de ImportacionDesignacionesControllerTest. */
    private function generarDocxDePrueba(): string
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        $section->addText("GRUPO 15\t\t\tSUB 15\t\t\t01 MARZO 07/08\t\t\tASOCIACION DE", ['color' => 'FF0000']);
        $table = $section->addTable();
        $table->addRow();
        foreach (['PARTIDO', 'SANTA FE', 'BETHEL', 'ARBITRO', '', 'ASOCAFA'] as $t) {
            $table->addCell()->addText($t);
        }
        $table->addRow();
        $table->addCell()->addText('ESTADIO    CENTRO DEPORTIVO 1');
        $table->addCell()->addText('LINEA UNO');
        $table->addCell()->addText('');
        $table->addCell()->addText('ASOCAFA');
        $table->addRow();
        foreach (['DIA', 'SABADO 7 MARZO', 'HORA', '09:00', 'LINEA DOS', '', 'ASOCAFA'] as $t) {
            $table->addCell()->addText($t);
        }
        $table->addRow();
        $table->addCell()->addText('CIUDAD                BOGOTA');
        $table->addCell()->addText('EMERGENTE');
        $table->addCell()->addText('');
        $table->addCell()->addText('ASOCAFA');

        $ruta = tempnam(sys_get_temp_dir(), 'novareef_dusk_docx_') . '.docx';
        \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007')->save($ruta);

        return $ruta;
    }

    public function test_subir_revisar_y_confirmar_crea_el_partido_real(): void
    {
        $colegio    = $this->crearColegio();
        $designador = $this->crearDesignadorConPermisos($colegio);
        $torneo     = $this->crearTorneo($colegio, $designador, ['temporada' => 2026]);
        DivisionTorneo::create(['idTorneo' => $torneo->idTorneo, 'nombreDivision' => 'SUB 15']);
        $this->crearSede($torneo)->update(['nombreSede' => 'Centro Deportivo 1']);
        $formato = $this->crearFormatoDupla();

        $ruta = $this->generarDocxDePrueba();

        $this->browse(function (Browser $browser) use ($designador, $torneo, $formato, $ruta) {
            $browser->loginAs($designador, 'web')
                ->visit('/designaciones/importar');

            // Torneo/Formato están mejorados con Choices.js (oculta el
            // <select> nativo) — se fija el valor por JS directo en vez de
            // simular clics en el widget de un tercero, que no es lo que
            // este test necesita verificar. script() no es encadenable:
            // devuelve los resultados de cada sentencia, no el Browser.
            $browser->script([
                "document.querySelector('select[name=idTorneo]').value = '{$torneo->idTorneo}';",
                "document.querySelector('select[name=idFormato]').value = '{$formato->idFormato}';",
            ]);

            $browser->attach('input[name=archivoWord]', $ruta)
                ->press('Subir y previsualizar')
                ->waitForText('Confirmar importación', 15)
                // "Santa Fe" vive en el value de un <input>, no en un nodo de
                // texto — assertSee() no lo encuentra, se verifica con
                // assertInputValue().
                ->assertInputValue('input[name*="[equipoLocal]"]', 'SANTA FE')
                // La fila debe venir pre-marcada (incluir por defecto) —
                // confirma antes de tocar nada más.
                ->assertChecked('input[name*="[incluir]"]')
                ->click('#btn-confirmar-importacion')
                ->waitFor('.swal2-confirm', 10)
                ->click('.swal2-confirm')
                ->waitForLocation('/novareef/public/designaciones', 15);
        });

        $this->assertSame(1, Partido::where('idTorneo', $torneo->idTorneo)->count());

        @unlink($ruta);
    }
}
