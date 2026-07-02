<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->sembrarEstadosBase();

        DB::statement("
            ALTER TABLE arbitros
            MODIFY COLUMN estadoArbitro VARCHAR(30) NOT NULL DEFAULT 'proceso_ingreso'
        ");

        DB::statement("
            UPDATE arbitros
            SET estadoArbitro = 'inactivo'
            WHERE estadoArbitro = 'aprendiz'
        ");

        DB::statement("
            ALTER TABLE arbitros
            ADD CONSTRAINT fk_arbitros_estado
            FOREIGN KEY (estadoArbitro)
            REFERENCES estados_arbitro(nombre)
            ON UPDATE CASCADE
            ON DELETE RESTRICT
        ");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE arbitros DROP FOREIGN KEY fk_arbitros_estado');

        DB::statement("
            ALTER TABLE arbitros
            MODIFY COLUMN estadoArbitro ENUM('activo','inactivo','suspendido','retirado','aprendiz','proceso_ingreso')
            NOT NULL DEFAULT 'proceso_ingreso'
        ");
    }

    private function sembrarEstadosBase(): void
    {
        $estados = [
            ['nombre' => 'proceso_ingreso', 'etiqueta' => 'En proceso de ingreso', 'color' => 'blue',  'permiteDesignar' => false, 'esActivo' => true, 'orden' => 1],
            ['nombre' => 'activo',          'etiqueta' => 'Activo',                'color' => 'green', 'permiteDesignar' => true,  'esActivo' => true, 'orden' => 2],
            ['nombre' => 'inactivo',        'etiqueta' => 'Inactivo',              'color' => 'gray',  'permiteDesignar' => false, 'esActivo' => true, 'orden' => 3],
            ['nombre' => 'suspendido',      'etiqueta' => 'Suspendido',            'color' => 'red',   'permiteDesignar' => false, 'esActivo' => true, 'orden' => 4],
            ['nombre' => 'retirado',        'etiqueta' => 'Retirado',              'color' => 'black', 'permiteDesignar' => false, 'esActivo' => true, 'orden' => 5],
        ];

        foreach ($estados as $estado) {
            $existe = DB::table('estados_arbitro')->where('nombre', $estado['nombre'])->exists();
            if (! $existe) {
                DB::table('estados_arbitro')->insert(array_merge($estado, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }
};
