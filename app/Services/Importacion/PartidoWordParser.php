<?php

declare(strict_types=1);

namespace App\Services\Importacion;

use PhpOffice\PhpWord\Element\AbstractContainer;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

/**
 * Extrae los partidos de un .docx de designaciones (formato de asociación
 * arbitral): un bloque de texto de contexto (GRUPO / CATEGORÍA / FECHA)
 * seguido de una tabla de 4 filas (PARTIDO / ESTADIO / DIA-HORA / CIUDAD a
 * la izquierda, ARBITRO / LINEA UNO / LINEA DOS / EMERGENTE a la derecha).
 *
 * No hace matching de división/sede (eso es MatchingTextoService) ni valida
 * contra el torneo — solo lee el documento tal cual.
 *
 * Anclaje por texto de etiqueta, no por índice fijo de celda: el documento
 * real no es perfectamente uniforme (ESTADIO/CIUDAD traen la etiqueta y el
 * valor pegados en una sola celda en unas filas, separados en otras; además
 * hay celdas sobrantes al final de varias filas, restos de una plantilla
 * reutilizada semana a semana). Buscar la celda que dice literalmente
 * "ESTADIO"/"ARBITRO"/etc. y tomar la(s) celda(s) siguiente(s) es robusto a
 * esa variación; indexar por posición fija no lo es.
 */
final class PartidoWordParser
{
    private const MESES = [
        'enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4,
        'mayo' => 5, 'junio' => 6, 'julio' => 7, 'agosto' => 8,
        'septiembre' => 9, 'setiembre' => 9, 'octubre' => 10,
        'noviembre' => 11, 'diciembre' => 12,
    ];

    /** Roles que puede traer la tabla, en el orden en que aparecen en el documento real. */
    private const ROLES_TABLA = ['ARBITRO', 'LINEA UNO', 'LINEA DOS', 'EMERGENTE'];

    /**
     * @return array<int, array{
     *     clave: string, grupoTexto: ?string, categoriaTexto: string, fechaTexto: string,
     *     asociacionTexto: ?string, equipoLocal: string, equipoVisitante: string,
     *     nombreSedeTexto: string, diaTexto: string, ciudadTexto: string,
     *     fechaPartido: ?string, horaPartido: ?string, errorFecha: bool,
     *     roles: array<int, array{rolTexto: string, nombreTexto: string, asociacionTexto: ?string}>,
     *     errorParseo: ?string,
     * }>
     */
    public function parsear(string $rutaArchivo, int $anioTorneo): array
    {
        $phpWord = IOFactory::load($rutaArchivo, 'Word2007');

        $partidos = [];
        $contexto = null;
        $numeroTabla = 0;

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $elemento) {
                if ($elemento instanceof Table) {
                    $numeroTabla++;

                    // Una tabla corrupta/inesperada no debe tumbar el resto
                    // del documento — se reporta como fila con error propio,
                    // aislado, y el resto se sigue procesando con normalidad.
                    try {
                        $partidos[] = $this->parsearTabla($elemento, $contexto, $anioTorneo);
                    } catch (\Throwable $e) {
                        $partidos[] = $this->filaConErrorDeParseo($contexto, $numeroTabla, $e);
                    }

                    $contexto = null;
                    continue;
                }

                $texto = $this->extraerTexto($elemento);
                if (trim($texto) !== '') {
                    $contexto = $this->parsearContexto($texto);
                }
            }
        }

        return $partidos;
    }

    /** @return array<string, mixed> */
    private function filaConErrorDeParseo(?array $contexto, int $numeroTabla, \Throwable $e): array
    {
        report($e);

        return [
            'clave'           => (string) \Illuminate\Support\Str::uuid(),
            'grupoTexto'      => $contexto['grupoTexto'] ?? null,
            'categoriaTexto'  => $contexto['categoriaTexto'] ?? '',
            'fechaTexto'      => $contexto['fechaTexto'] ?? '',
            'asociacionTexto' => null,
            'equipoLocal'     => '',
            'equipoVisitante' => '',
            'nombreSedeTexto' => '',
            'diaTexto'        => '',
            'ciudadTexto'     => '',
            'fechaPartido'    => null,
            'horaPartido'     => null,
            'errorFecha'      => true,
            'roles'           => [],
            'errorParseo'     => "No se pudo leer la tabla #{$numeroTabla} del documento — revisa manualmente esta fila o corrige el Word y vuelve a subirlo.",
        ];
    }

    private function parsearContexto(string $texto): array
    {
        $segmentos = array_values(array_filter(
            array_map('trim', preg_split('/\t+/', $texto) ?: []),
            fn (string $s) => $s !== '',
        ));

        return [
            'grupoTexto'     => $segmentos[0] ?? null,
            'categoriaTexto' => $segmentos[1] ?? '',
            'fechaTexto'     => $segmentos[2] ?? '',
        ];
    }

    private function parsearTabla(Table $table, ?array $contexto, int $anioTorneo): array
    {
        $filas = array_map(
            fn ($row) => array_map(
                fn ($cell) => trim($this->extraerTexto($cell)),
                $row->getCells(),
            ),
            $table->getRows(),
        );

        $celdasPlano = array_merge(...$filas);

        $idxPartido      = $this->indiceCelda($celdasPlano, 'PARTIDO');
        $equipoLocal     = $celdasPlano[$idxPartido + 1] ?? '';
        $equipoVisitante = $celdasPlano[$idxPartido + 2] ?? '';

        $diaTexto  = $this->valorTrasEtiqueta($celdasPlano, 'DIA');
        $horaTexto = $this->valorTrasEtiqueta($celdasPlano, 'HORA');

        $nombreSedeTexto = $this->valorConEtiquetaPosiblementePegada($celdasPlano, 'ESTADIO');
        $ciudadTexto     = $this->valorConEtiquetaPosiblementePegada($celdasPlano, 'CIUDAD');

        $roles           = $this->extraerRoles($celdasPlano);
        $asociacionTexto = $roles[0]['asociacionTexto'] ?? null;

        [$fechaPartido, $horaPartido, $errorFecha] = $this->interpretarFechaHora($diaTexto, $horaTexto, $anioTorneo);

        return [
            'clave'           => (string) \Illuminate\Support\Str::uuid(),
            'grupoTexto'      => $contexto['grupoTexto'] ?? null,
            'categoriaTexto'  => $contexto['categoriaTexto'] ?? '',
            'fechaTexto'      => $contexto['fechaTexto'] ?? '',
            'asociacionTexto' => $asociacionTexto,
            'equipoLocal'     => $equipoLocal,
            'equipoVisitante' => $equipoVisitante,
            'nombreSedeTexto' => $nombreSedeTexto,
            'diaTexto'        => $diaTexto,
            'ciudadTexto'     => $ciudadTexto,
            'fechaPartido'    => $fechaPartido,
            'horaPartido'     => $horaPartido,
            'errorFecha'      => $errorFecha,
            'roles'           => $roles,
            'errorParseo'     => null,
        ];
    }

    /** Índice de la celda cuyo texto (normalizado) es exactamente $etiqueta. */
    private function indiceCelda(array $celdas, string $etiqueta): int
    {
        $buscado = mb_strtoupper(trim($etiqueta));

        foreach ($celdas as $i => $texto) {
            if (mb_strtoupper(trim($texto)) === $buscado) {
                return $i;
            }
        }

        return -1;
    }

    /** Caso "DIA | valor" / "HORA | valor": etiqueta y valor en celdas separadas. */
    private function valorTrasEtiqueta(array $celdas, string $etiqueta): string
    {
        $idx = $this->indiceCelda($celdas, $etiqueta);

        return $idx >= 0 ? trim($celdas[$idx + 1] ?? '') : '';
    }

    /**
     * Caso "ESTADIO    Centro Deportivo 1" pegado en una sola celda —
     * también soporta el caso en que sí vengan separadas, por consistencia.
     */
    private function valorConEtiquetaPosiblementePegada(array $celdas, string $etiqueta): string
    {
        $idx = $this->indiceCelda($celdas, $etiqueta);
        if ($idx >= 0) {
            return trim($celdas[$idx + 1] ?? '');
        }

        $buscado = mb_strtoupper($etiqueta);
        foreach ($celdas as $texto) {
            $normalizado = mb_strtoupper(ltrim($texto));
            if (str_starts_with($normalizado, $buscado)) {
                return trim(mb_substr($texto, mb_strlen($etiqueta)));
            }
        }

        return '';
    }

    /**
     * Extrae, para cada uno de los 4 roles (ARBITRO/LINEA UNO/LINEA DOS/
     * EMERGENTE), el nombre del árbitro designado por la asociación y SU
     * PROPIA asociación — no se puede asumir que solo el emergente cambia
     * de asociación: cualquiera de los 4 roles puede venir de una asociación
     * (colegio) distinta al dueño del torneo, la del contexto rojo no
     * aplica ("ASOCIACION DE" ahí siempre viene sin valor).
     *
     * @return array<int, array{rolTexto: string, nombreTexto: string, asociacionTexto: ?string}>
     */
    private function extraerRoles(array $celdas): array
    {
        $roles = [];

        foreach (self::ROLES_TABLA as $rolTexto) {
            $idx = $this->indiceCelda($celdas, $rolTexto);
            if ($idx < 0) {
                continue;
            }

            $nombreTexto     = trim($celdas[$idx + 1] ?? '');
            $asociacionTexto = trim($celdas[$idx + 2] ?? '');

            $roles[] = [
                'rolTexto'        => $rolTexto,
                'nombreTexto'     => $nombreTexto,
                'asociacionTexto' => $asociacionTexto !== '' ? $asociacionTexto : null,
            ];
        }

        return $roles;
    }

    /**
     * @return array{0: ?string, 1: ?string, 2: bool} [fechaPartido 'Y-m-d', horaPartido 'H:i', huboError]
     */
    private function interpretarFechaHora(string $diaTexto, string $horaTexto, int $anioTorneo): array
    {
        if (! preg_match('/(\d{1,2})\s+([a-záéíóú]+)/ui', $diaTexto, $m)) {
            return [null, null, true];
        }

        $dia = (int) $m[1];
        $mes = self::MESES[mb_strtolower($m[2])] ?? null;

        if ($mes === null || ! checkdate($mes, $dia, $anioTorneo)) {
            return [null, null, true];
        }

        if (! preg_match('/(\d{1,2}):(\d{2})/', $horaTexto, $hm)) {
            return [null, null, true];
        }

        $hora    = (int) $hm[1];
        $minuto  = (int) $hm[2];
        if ($hora > 23 || $minuto > 59) {
            return [null, null, true];
        }

        $fecha = sprintf('%04d-%02d-%02d', $anioTorneo, $mes, $dia);
        $horaF = sprintf('%02d:%02d', $hora, $minuto);

        return [$fecha, $horaF, false];
    }

    private function extraerTexto(mixed $elemento): string
    {
        if (method_exists($elemento, 'getText')) {
            $texto = $elemento->getText();

            return is_string($texto) ? $texto : '';
        }

        if ($elemento instanceof AbstractContainer) {
            $acumulado = '';
            foreach ($elemento->getElements() as $hijo) {
                $acumulado .= $this->extraerTexto($hijo);
            }

            return $acumulado;
        }

        return '';
    }
}
