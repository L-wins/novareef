# Plan: Importador de partidos desde Word (.docx) + Exportador PDF con árbitros designados

## Contexto

Al designador de un colegio le llega, por cada jornada, un archivo Word (.docx) de hasta 9 páginas
con decenas de partidos, siempre en el mismo formato consistente (confirmado que se repite igual
entre varios colegios de la liga). Cada partido es un bloque de texto en rojo (GRUPO / CATEGORÍA /
FECHA / ASOCIACIÓN) seguido de una tabla de 4 filas con PARTIDO / ESTADIO / DIA-HORA / CIUDAD a la
izquierda y ARBITRO / LINEA UNO / LINEA DOS / EMERGENTE a la derecha (estas últimas **siempre
llegan en blanco** en el Word — la designación de árbitros ocurre después, dentro de la
plataforma).

Hoy el designador tendría que crear cada uno de esos partidos a mano en `/designaciones/crear`,
uno por uno, para un archivo que puede traer decenas de partidos repartidos en varias divisiones.
Este plan cubre: (1) subir el .docx y crear automáticamente los partidos en borrador sin árbitros,
(2) reutilizar el flujo M04 ya existente para designar árbitros normalmente, y (3) exportar un PDF
con el mismo formato visual del Word, pero ya con los árbitros asignados, para que el designador se
lo reenvíe a su jefe/asociación.

**Módulo afectado:** Designaciones (M04, área designador/ejecutivo)

---

## 0. Decisiones de diseño confirmadas (no se cuestionan)

1. **"GRUPO N" se guarda en `partidos.observaciones`** (columna `text nullable` ya existente). No
   se crea ninguna migración ni columna nueva. Razón: `DivisionTorneo.descripcion` es un campo
   compartido por todos los partidos de una división ("PRIMERA C"), y una misma división puede
   agrupar varios "GRUPO N" distintos (GRUPO 10, GRUPO 11...) — no puede llevar los dos valores a
   la vez. `observaciones` es per-partido, así que cada partido guarda su propio grupo sin
   colisionar con los demás partidos de la misma división. Formato de guardado:
   `observaciones = "GRUPO 10"` (o se concatena con salto de línea si en el futuro hay
   observaciones adicionales).
2. Torneo y División deben existir de antemano. El importador NUNCA crea `Torneo` ni
   `DivisionTorneo`. Si no hay match de división, el partido se marca en error en el preview y no
   se importa (o queda pendiente de corrección manual antes de confirmar).
   1. **El Word trae múltiples divisiones/categorías distintas en el mismo archivo** (ej. "PRIMERA
      C" en el bloque del GRUPO 10, pero puede haber otros bloques más adelante en las 9 páginas
      con "SEGUNDA B", "PRIMERA A", etc.). El usuario **NO** preselecciona una única división en el
      formulario de subida — solo selecciona el **Torneo**. El matching de división (sección 3)
      corre **individualmente por cada partido parseado**, usando el texto de categoría de ESE
      bloque específico contra las `DivisionTorneo` del torneo seleccionado. Es perfectamente
      válido (y esperado) que el resultado de un mismo archivo importado tenga partidos con
      `idDivisionMatch` distintos entre sí. Esto ya estaba implícito en el diseño de "matching por
      fila" de la sección 3, pero se deja explícito aquí para no reintroducir por error un selector
      global de división en el formulario de subida (sección 4, Estado 1).
3. Sede sin match -> `idSede = null`, advertencia visible, no bloquea importación.
4. `equipoLocal` / `equipoVisitante` son texto libre, se copian tal cual, sin matching.
5. Las columnas ARBITRO / LINEA UNO / LINEA DOS / EMERGENTE del Word se ignoran por completo al
   parsear (siempre llegan en blanco). La designación de árbitros ocurre después, dentro de la
   plataforma, vía M04 ya existente (`DesignacionController@show`, `asignarArbitro`, etc. — no se
   toca ese flujo).

---

## 1. Estructura de datos intermedia (preview antes de confirmar)

**Decisión: sesión (`session()`), NO tabla de staging en BD.**

Justificación:
- Un archivo de 9 páginas con "decenas de partidos" (estimando hasta ~80-100 bloques en el peor
  caso) serializado a un array asociativo simple (sin relaciones Eloquent, sin objetos pesados)
  pesa unos pocos KB a bajos cientos de KB — muy por debajo de límites razonables de sesión. Se
  debe confirmar el driver de sesión configurado (`config/session.php` / `SESSION_DRIVER` en
  `.env`); dado que el proyecto ya usa Redis (Reverb), lo más probable es que la sesión también use
  Redis o database, no cookie — confirmar antes de implementar.
- Evita el costo de una migración nueva + modelo nuevo + limpieza de registros huérfanos (partidos
  parseados que el usuario nunca confirma) que una tabla de staging conllevaría.
- El ciclo de vida es corto: subir -> preview -> confirmar/descartar, todo en la misma sesión de
  trabajo del designador.

**Estructura guardada en `session('importacion_designaciones')`:**

```php
[
    'idTorneo' => int,
    'idColegio' => int,              // valida que no cambie el colegio activo entre pasos
    'nombreArchivoOriginal' => string,
    'idFormatoDefault' => int|null,  // formato elegido globalmente en el paso 1
    'partidos' => [
        [
            'clave' => string,             // uuid corto generado al parsear, key de fila en el form de preview
            'grupoTexto' => string|null,   // "GRUPO 10" tal cual -> va a observaciones
            'categoriaTexto' => string,    // "PRIMERA C" tal cual
            'fechaTexto' => string,        // "09 JULIO 04/05" tal cual (crudo, referencia visual)
            'asociacionTexto' => string|null,
            'equipoLocal' => string,
            'equipoVisitante' => string,
            'nombreSedeTexto' => string,   // "NICO 7" tal cual
            'diaTexto' => string,          // texto crudo de la fila DIA
            'fechaPartido' => string|null, // 'Y-m-d' ya parseado, o null si no se pudo interpretar
            'horaPartido' => string|null,  // 'H:i' ya parseado, o null
            'ciudadTexto' => string|null,
            'idDivisionMatch' => int|null,
            'idSedeMatch' => int|null,
            'idFormato' => int|null,       // override individual; si null, usa idFormatoDefault al confirmar
            'advertencias' => [],          // ej. ['Sede "NICO 7" no encontrada, se importara sin sede']
            'errores' => [],               // ej. ['Division "PRIMERA D" no existe en el torneo']
            'incluir' => true,             // checkbox del preview; false por defecto si tiene errores bloqueantes
        ],
    ],
]
```

- El array `partidos` se reconstruye cuando el usuario edita algo en el preview (submit completo
  del formulario que re-renderiza con valores corregidos y re-guarda en sesión) — no requiere AJAX
  fila-por-fila en la v1.
- Al confirmar exitosamente o al cancelar, se limpia `session()->forget('importacion_designaciones')`.

---

## 2. Parser del .docx

### Librería
Agregar a `composer.json`:
```
composer require phpoffice/phpword
```
Confirmado: `phpoffice/phpword` NO está en `composer.json` actual (solo `barryvdh/laravel-dompdf`,
`bacon/bacon-qr-code`, etc.) — hay que agregarlo.

### Cómo PhpWord expone el documento
`PhpOffice\PhpWord\IOFactory::load($path)` devuelve un `PhpWord` con `getSections()`. Cada
`Section` tiene `getElements()`, que retorna, en el **orden secuencial real del documento**, una
mezcla de:
- `PhpOffice\PhpWord\Element\TextRun` / `PhpOffice\PhpWord\Element\Text` -> párrafos de texto suelto
  (el bloque rojo "GRUPO 10 / PRIMERA C / fecha / asociación").
- `PhpOffice\PhpWord\Element\Table` -> la tabla de 4 filas del partido.

Se distingue el tipo de cada elemento con `$element instanceof \PhpOffice\PhpWord\Element\Table` vs
`TextRun`/`Text`. Como los elementos vienen en orden de aparición, basta iterar linealmente llevando
"el último bloque de texto visto" como contexto pendiente; cuando aparece una `Table`, se asocia ese
contexto con esa tabla y se resetea para el siguiente bloque.

### Clase: `App\Services\Importacion\PartidoWordParser`
Archivo: `app/Services/Importacion/PartidoWordParser.php`

```php
final class PartidoWordParser
{
    /** @return array<int, array> lista de partidos crudos parseados (sin matching de division/sede todavia) */
    public function parsear(string $rutaArchivo): array
    {
        $phpWord = \PhpOffice\PhpWord\IOFactory::load($rutaArchivo);
        $partidos = [];
        $contextoPendiente = null; // ultimo texto "GRUPO N / CATEGORIA / FECHA / ASOCIACION" visto

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if ($this->esTexto($element)) {
                    $texto = $this->extraerTextoPlano($element);
                    if (trim($texto) !== '') {
                        $contextoPendiente = $this->parsearLineaContexto($texto, $contextoPendiente);
                    }
                    continue;
                }

                if ($element instanceof \PhpOffice\PhpWord\Element\Table) {
                    $partidos[] = $this->parsearTablaPartido($element, $contextoPendiente);
                    $contextoPendiente = null; // se consume; el siguiente texto es del proximo bloque
                }
            }
        }

        return $partidos;
    }

    private function parsearLineaContexto(string $texto, ?array $contextoPrevio): array { /* ... */ }
    private function parsearTablaPartido(\PhpOffice\PhpWord\Element\Table $table, ?array $contexto): array { /* ... */ }
    private function esTexto($element): bool { /* ... */ }
    private function extraerTextoPlano($element): string { /* recorre runs internos de Text/TextRun y concatena */ }
}
```

**Detalle sobre el "contexto"**: el texto rojo trae 4 datos separados por posición (izq/centro/fecha/der),
pero puede venir en una sola línea con tabs/espacios, o en varios `Text`/`TextRun` consecutivos
antes de la tabla. `parsearLineaContexto` debe ir *acumulando* (merge, no reemplazo) en
`$contextoPendiente` hasta llegar la tabla — soporta ambos casos. Heurística explícita para asignar
cada línea de texto a su campo:
- Si matchea `/^GRUPO\s+\d+/i` -> es el grupo.
- Si matchea un patrón de fecha tipo `/\d{1,2}\s+[A-ZA-Z]+\s+\d{2}\/\d{2}/i` (día + mes + año/año)
  -> es la fecha textual.
- Si empieza con `ASOCIACION` (con o sin tilde) -> es la asociación.
- Cualquier otra línea no vacía que no matchee los patrones anteriores se asume categoría (es el
  único campo "libre" del grupo de contexto).
- Líneas que no matchean nada y no son candidatas a categoría (ej. párrafos vacíos, saltos de
  formato) se descartan silenciosamente, logueando a nivel debug para poder ajustar la heurística
  si aparecen variaciones en otros documentos.

**Parseo de la tabla (`parsearTablaPartido`)**: se accede a filas por índice fijo, ya que el
formato es 100% consistente (confirmado, no varía):
```php
$rows = $table->getRows();
// $rows[0] = fila PARTIDO: celda[0]="PARTIDO", celda[1]=equipoLocal, celda[2]=equipoVisitante, celda[3]="ARBITRO" (ignorar), celda[4]=blanco (ignorar), celda[5]=asociacion (redundante con el contexto, se prioriza el contexto)
// $rows[1] = fila ESTADIO: celda[0]="ESTADIO", celda[1]=nombreSede, celda[3]="LINEA UNO" (ignorar)
// $rows[2] = fila DIA: celda[0]="DIA", celda[1]=diaTexto+fecha, celda[2]="HORA", celda[3]=hora, celda[4]="LINEA DOS" (ignorar)
// $rows[3] = fila CIUDAD: celda[0]="CIUDAD", celda[1]=ciudad, celda[3]="EMERGENTE" (ignorar)
```
Cada celda se lee con un helper `textoCelda(Row $row, int $indice): string` que extrae el texto
plano de `$row->getCells()[$indice]` (una celda de tabla en PhpWord contiene a su vez elementos
`TextRun`, se recorren igual que en `extraerTextoPlano`). **Nunca se leen las celdas de
ARBITRO/LINEA UNO/LINEA DOS/EMERGENTE** — ni siquiera para validarlas, se ignoran por índice, tal
como exige el contexto de negocio.

**Parseo de fecha/hora**: la fila DIA trae texto tipo "JUEVES 09/07" y hora tipo "3:00 PM" o
similar — usar `Carbon::createFromFormat` con manejo de excepción envuelto: si no se puede parsear
con los formatos esperados (definir 2-3 formatos candidatos), `fechaPartido`/`horaPartido` quedan
`null` y se agrega a `errores` (bloqueante, ya que `Partido.fechaPartido`/`horaPartido` son NOT
NULL). El año exacto del partido probablemente deba inferirse combinando el día/mes de la fila DIA
con el `temporada`/año del Torneo seleccionado (el "04/05" del encabezado es categoría de nacidos,
no el año del partido) — **esto debe confirmarse contra un .docx real de muestra antes de fijar el
parser** (ver Riesgos).

---

## 3. Matching de División y Sede

### Servicio: `App\Services\Importacion\MatchingTextoService`
Archivo: `app/Services/Importacion/MatchingTextoService.php`

```php
final class MatchingTextoService
{
    public function normalizar(string $texto): string
    {
        $t = mb_strtolower(trim($texto));
        $t = preg_replace('/\s+/', ' ', $t);                       // colapsa espacios multiples
        $t = \Illuminate\Support\Str::of($t)->ascii()->toString(); // quita tildes/diacriticos
        return $t;
    }

    /** @return int|null idDivision si hay match exacto normalizado, null si no */
    public function matchearDivision(string $nombreDivisionWord, int $idTorneo): ?int
    {
        $normalizado = $this->normalizar($nombreDivisionWord);

        return \App\Models\DivisionTorneo::where('idTorneo', $idTorneo)
            ->get(['idDivision', 'nombreDivision'])
            ->first(fn ($d) => $this->normalizar($d->nombreDivision) === $normalizado)
            ?->idDivision;
    }

    /** @return int|null idSede si hay match exacto normalizado, null si no */
    public function matchearSede(string $nombreSedeWord, int $idTorneo): ?int
    {
        $normalizado = $this->normalizar($nombreSedeWord);

        return \App\Models\SedeTorneo::where('idTorneo', $idTorneo)
            ->get(['idSede', 'nombreSede'])
            ->first(fn ($s) => $this->normalizar($s->nombreSede) === $normalizado)
            ?->idSede;
    }
}
```

- Estrategia: **case-insensitive + trim + sin tildes + espacios colapsados**, comparación
  **exacta** (no fuzzy/similarity) tras normalizar — más predecible para el designador que un
  fuzzy-match que podría matchear mal en silencio. Si el Word trae variaciones más allá de
  mayúsculas/tildes (ej. "PRIMERA C " vs "1ERA C"), se resuelve manualmente en el preview (el
  usuario corrige el select de división/sede fila por fila) en vez de complicar el matcher con
  heurística adicional en v1.
- Traer todas las divisiones/sedes del torneo con un solo query cada una (no N+1 por partido) y
  reutilizar la colección para todos los partidos del lote.

---

## 4. Pantalla de preview

### Ruta y vista
`GET /designaciones/importar` -> formulario de subida (selector de Torneo + input file .docx +
selector de Formato de designación *default* aplicado a todo el lote).
`POST /designaciones/importar` -> procesa el archivo (parser + matching), guarda resultado en
sesión, redirige a la misma vista de preview mostrando la tabla editable.
`POST /designaciones/importar/confirmar` -> toma lo que está en sesión (ya corregido por el usuario
en el preview), llama al servicio de importación en lote, crea los partidos, limpia sesión,
redirige a `designaciones.index` con torneo preseleccionado.

### Vista: `resources/views/designaciones/importar.blade.php`
Dos estados renderizados por la misma vista (según si `session('importacion_designaciones')`
existe):

**Estado 1 — sin datos en sesión:** formulario simple
- Select Torneo (`data-nova-select`, mismo patrón que `partido-crear.blade.php`)
- Input `<input type="file" name="archivoWord" accept=".docx">`
- Select Formato de designación default (`FormatoDesignacion::activos()`)
- Botón "Subir y previsualizar"

**Estado 2 — con datos en sesión (preview):**
- Resumen arriba: nombre archivo, torneo, cantidad total de partidos detectados, cantidad con
  error (no importables), cantidad con advertencia (importables con hueco), y **desglose por
  división detectada** (ej. "PRIMERA C: 14 partidos · SEGUNDA B: 9 partidos · Sin división
  reconocida: 3 partidos") — dado que un mismo archivo trae varias divisiones (ver 2.1), este
  desglose ayuda al designador a confirmar de un vistazo que ninguna categoría del Word quedó
  fuera del matching antes de revisar fila por fila.
- Tabla editable, agrupada visualmente por `categoriaTexto` (misma agrupación que el desglose de
  arriba, usando encabezados de grupo tipo `<tbody>` separados o una fila divisoria con el nombre
  de categoría) para que el designador revise cada bloque de división junto en vez de una lista
  plana de decenas de filas sin orden. Una fila por partido parseado dentro de su grupo (HTML + JS
  vanilla, el proyecto no usa Livewire aquí):
  - Checkbox "Incluir" (precargado según `incluir` calculado)
  - Grupo (input texto editable — va a `observaciones`)
  - Categoría/División Word (texto crudo, solo lectura) + select "División real"
    (`data-nova-select`, poblado con divisiones del torneo; preseleccionado si hubo match, vacío +
    fila resaltada en rojo si no hubo match)
  - Equipo local / Equipo visitante (inputs texto editables)
  - Sede Word (texto crudo) + select "Sede real" (poblado con sedes del torneo + opción "Sin
    sede"; preseleccionado si hubo match, o "Sin sede" resaltado en ámbar si no hubo match)
  - Fecha (input date) / Hora (input time) — si no se pudo parsear, quedan vacíos y la fila se
    marca en rojo como error bloqueante
  - Formato de designación (select individual, precargado con el default global, editable por
    fila)
  - Columna "Estado": rojo si `errores` no vacío ("No se puede importar: división no encontrada"),
    ámbar si solo `advertencias` ("Se importará sin sede"), verde si todo OK.
- Botón "Confirmar importación" (validado también server-side para que ninguna fila incluida tenga
  errores).
- Botón "Cancelar" -> `POST /designaciones/importar/cancelar` (limpia sesión).

Cada edición de fila se hace vía un **submit completo del formulario de preview** a
`POST /designaciones/importar/revisar`, que re-valida y re-guarda en sesión con los valores
corregidos (recalculando `errores`/`advertencias`). Evita complejidad de AJAX fila-por-fila en la
v1; el volumen (decenas de filas) es manejable con un POST normal.

### JS: `resources/js/designaciones/importar-designaciones.js`
Módulo separado (JS no inline en Blade, según convención del proyecto), registrado en
`vite.config.js` junto a su CSS. Responsabilidades:
- Inicializar Choices.js en los selects de división/sede/formato por fila (`data-nova-select`),
  igual patrón que `resources/js/shared/nova-selects.js` ya usa en el resto del módulo.
- Manejar el toggle visual de "Incluir" (deshabilita inputs de la fila si se desmarca).
- Confirmación SweetAlert2 (`window.novaAlert`) antes de "Confirmar importación", mostrando resumen
  (ej. "Se crearán 42 partidos, 3 quedarán sin sede asignada. ¿Continuar?").
- Validación cliente mínima antes de submit (fecha/hora no vacías en filas incluidas).

### CSS
`resources/css/designaciones/importar-designaciones.css` (o reutilizar `designaciones.css` si ya
trae estilos de tabla reutilizables — confirmar contenido antes de decidir crear archivo nuevo).
Usa tokens `--nv-*` existentes, sin Tailwind (patrón confirmado: el panel de usuario no usa
Tailwind, `partido-crear.blade.php` usa `form-input`/`form-label`/`form-error` con CSS propio).

---

## 5. Servicio de importación en lote

### Clase: `App\Services\Importacion\ImportacionPartidosService`
Archivo: `app/Services/Importacion/ImportacionPartidosService.php`

```php
final class ImportacionPartidosService
{
    public function __construct(
        private readonly DesignacionService $designaciones,
    ) {}

    /**
     * Crea todos los partidos incluidos y sin errores bloqueantes del lote.
     * Transaccion unica para todo el lote: todo-o-nada.
     *
     * @param array $partidosData filas ya corregidas/confirmadas desde el preview (sesion)
     * @return array{creados: int, partidos: Collection<Partido>}
     * @throws \RuntimeException si alguna fila incluida sigue teniendo error bloqueante
     */
    public function importarLote(int $idColegio, int $idTorneo, array $partidosData, int $idUsuarioAccion): array
    {
        $filasAImportar = array_filter($partidosData, fn ($fila) => $fila['incluir']);

        foreach ($filasAImportar as $fila) {
            if (!empty($fila['errores'])) {
                throw new \RuntimeException("El partido {$fila['equipoLocal']} vs {$fila['equipoVisitante']} tiene errores sin resolver.");
            }
        }

        return DB::transaction(function () use ($filasAImportar, $idColegio, $idTorneo, $idUsuarioAccion) {
            $creados = collect();

            foreach ($filasAImportar as $fila) {
                $observaciones = trim(
                    ($fila['grupoTexto'] ?? '') . (!empty($fila['observacionesExtra']) ? "\n" . $fila['observacionesExtra'] : '')
                ) ?: null;

                $partido = $this->designaciones->crearPartido($idColegio, [
                    'idTorneo'        => $idTorneo,
                    'idDivision'      => $fila['idDivisionMatch'],
                    'idSede'          => $fila['idSedeMatch'],
                    'idFormato'       => $fila['idFormato'],
                    'equipoLocal'     => $fila['equipoLocal'],
                    'equipoVisitante' => $fila['equipoVisitante'],
                    'fechaPartido'    => $fila['fechaPartido'],
                    'horaPartido'     => $fila['horaPartido'],
                    'observaciones'   => $observaciones,
                ], $idUsuarioAccion);

                $creados->push($partido);
            }

            return ['creados' => $creados->count(), 'partidos' => $creados];
        });
    }
}
```

Decisión: transacción todo-o-nada para el lote. Justificación:
- El preview ya filtró y marcó errores antes de llegar aquí; si algo falla en este punto es una
  condición excepcional, no el camino feliz. Una importación parcial con reporte de éxitos/fallos
  agregaría complejidad de UI para un caso que en la práctica no debería ocurrir si el preview hizo
  bien su trabajo.
- Es más seguro para el usuario: o el lote completo entra limpio, o nada entra, y puede corregir el
  preview y reintentar sin dejar partidos huérfanos a medias.
- `DesignacionService::crearPartido()` ya abre su propia sub-transacción por partido (Partido +
  slots + historial); al envolver todas las llamadas en una transacción exterior, las transacciones
  anidadas se comportan como savepoints, sin problema (patrón estándar de Laravel/MySQL).

No se crea `Partido::create()` directo. Se reutiliza `DesignacionService::crearPartido()` fila por
fila, tal como exige el contexto de negocio, para no perder slots ni historial.

---

## 6. Rutas y Controller

### Nuevo controller (separado de `DesignacionController`, que ya tiene 897 líneas, no debe crecer más)
Archivo: `app/Http/Controllers/Designacion/ImportacionDesignacionesController.php`

```php
namespace App\Http\Controllers\Designacion;

final class ImportacionDesignacionesController extends Controller
{
    use ResuelveColegio;

    public function __construct(
        private readonly PartidoWordParser $parser,
        private readonly MatchingTextoService $matcher,
        private readonly ImportacionPartidosService $importador,
    ) {}

    public function mostrar(): View {}
    public function procesar(Request $request): RedirectResponse {}
    public function revisar(Request $request): RedirectResponse {}
    public function confirmar(Request $request): RedirectResponse {}
    public function cancelar(): RedirectResponse {}
}
```
Responsabilidades: `mostrar` = GET del estado 1 o 2 según sesión. `procesar` = recibe archivo,
valida, parsea, matchea, guarda en sesión, redirige a mostrar. `revisar` = recibe correcciones del
preview, recalcula errores/advertencias, re-guarda en sesión. `confirmar` = llama a
`ImportacionPartidosService`, limpia sesión, redirige a `designaciones.index`. `cancelar` = limpia
sesión, redirige a mostrar.

### Rutas nuevas en `routes/web.php`
Dentro del grupo existente (confirmado en el archivo real, línea ~167):
```php
Route::prefix('designaciones')->name('designaciones.')->middleware(['permission:ver-designaciones', 'modulo:designaciones'])->group(function () {
    // ... rutas existentes (index, crear, show, etc.) ...

    Route::prefix('importar')->name('importar.')->middleware('permission:crear-designaciones')->group(function () {
        Route::get('/',            [ImportacionDesignacionesController::class, 'mostrar'])->name('mostrar');
        Route::post('/',           [ImportacionDesignacionesController::class, 'procesar'])->name('procesar');
        Route::post('/revisar',    [ImportacionDesignacionesController::class, 'revisar'])->name('revisar');
        Route::post('/confirmar',  [ImportacionDesignacionesController::class, 'confirmar'])->name('confirmar');
        Route::post('/cancelar',   [ImportacionDesignacionesController::class, 'cancelar'])->name('cancelar');
    });
});
```
Nombres de ruta resultantes: `designaciones.importar.mostrar`, `designaciones.importar.procesar`,
`designaciones.importar.revisar`, `designaciones.importar.confirmar`,
`designaciones.importar.cancelar` (patrón `designaciones.importar.*` requerido).

Agregar el `use App\Http\Controllers\Designacion\ImportacionDesignacionesController;` al bloque de
`use` de rutas, junto a los demás controllers de designaciones ya importados en `routes/web.php`.

Agregar un enlace "Importar desde Word" en `resources/views/designaciones/index.blade.php` (junto
al botón existente que lleva a `designaciones.create`), protegido por el mismo permiso
`crear-designaciones`.

### Validación del archivo subido (`procesar`)
```php
$validated = $request->validate([
    'idTorneo'    => 'required|integer',
    'idFormato'   => 'required|integer',
    'archivoWord' => 'required|file|mimes:docx|max:10240',
]);

$torneo = Torneo::where('idTorneo', $validated['idTorneo'])->where('idColegio', $idColegio)->firstOrFail();
```
Guardar el archivo temporalmente con `Storage::disk('local')->putFile('importaciones-temp', ...)`
solo durante el procesamiento de esa request; borrarlo tras parsear (no persiste, solo se necesita
el resultado parseado en sesión).

---

## 7. Vista PDF de exportación

### Ruta nueva
`GET /designaciones/torneo/{idTorneo}/listado-pdf` (bajo el mismo prefix `designaciones`, permiso
`ver-designaciones` como `generarActa`) — exporta todos los partidos de un torneo (con filtros
opcionales de división/fecha, igual patrón que `index()`) en el formato visual del Word.

Recomendación concreta para no seguir engordando `DesignacionController.php` (897 líneas, ya cerca
del límite de 600-700): crear `app/Http/Controllers/Designacion/ExportacionPdfController.php`,
mover ahí `generarActa()` desde `DesignacionController` y agregar el nuevo `generarListado()`.
Actualizar la ruta `designaciones.partido.acta` para apuntar al nuevo controller.

```php
public function generarListado(Request $request, int $idTorneo): mixed
{
    $idColegio = $this->idColegioActivo();
    $torneo = Torneo::where('idTorneo', $idTorneo)->where('idColegio', $idColegio)->firstOrFail();

    $query = Partido::where('idColegio', $idColegio)
        ->where('idTorneo', $idTorneo)
        ->with([
            'division', 'sede', 'formato',
            'designaciones' => fn ($q) => $q->whereIn('estadoDesignacion', [Designacion::ESTADO_PENDIENTE, Designacion::ESTADO_CONFIRMADA]),
            'designaciones.arbitro.usuario',
            'designaciones.rol',
        ])
        ->orderBy('fechaPartido')->orderBy('horaPartido');

    if ($request->filled('division')) { $query->where('idDivision', $request->integer('division')); }
    if ($request->filled('desde'))    { $query->whereDate('fechaPartido', '>=', $request->string('desde')); }
    if ($request->filled('hasta'))    { $query->whereDate('fechaPartido', '<=', $request->string('hasta')); }

    $partidos = $query->get();

    $pdf = app('dompdf.wrapper');
    $pdf->loadView('pdf.listado-partidos', ['partidos' => $partidos, 'torneo' => $torneo, 'generadoPor' => Auth::user()]);
    $pdf->setPaper('a4', 'portrait');

    return $pdf->download("listado-partidos-{$torneo->nombreTorneo}-" . now()->format('Y-m-d') . '.pdf');
}
```

### Cómo se obtienen ARBITRO / LINEA UNO / LINEA DOS / EMERGENTE por partido
Se agrupan las designaciones del partido por nombre de rol. En la vista Blade:
```blade
@php
    $porRol = $partido->designaciones->keyBy(fn ($d) => $d->rol?->nombre);
@endphp
<td>{{ $porRol->get('Central')?->arbitro?->usuario?->nombreUsuario ?? '' }}</td>
<td>{{ $porRol->get('Linea 1')?->arbitro?->usuario?->nombreUsuario ?? '' }}</td>
<td>{{ $porRol->get('Linea 2')?->arbitro?->usuario?->nombreUsuario ?? '' }}</td>
<td>{{ $porRol->get('Emergente')?->arbitro?->usuario?->nombreUsuario ?? '' }}</td>
```
ACCIÓN PREVIA OBLIGATORIA en fase de implementación: consultar el seeder de roles
(`database/seeders`, ej. `RolPartidoSeeder`) para confirmar los nombres exactos guardados en
`roles_partido.nombre` y ajustar las claves del `keyBy`/`get()` — no asumir el nombre textual sin
verificarlo primero.

### Vista: `resources/views/pdf/listado-partidos.blade.php`
Estructura, replicando visualmente el formato del Word (mismo patrón de `acta-designacion.blade.php`:
HTML/CSS inline, fuente DejaVu Sans, layout con `display:table` para las filas tipo label/valor):
- Header simple igual al de `acta-designacion.blade.php` (NovaReef, colegio, fecha de generación).
- Loop por cada partido, un bloque por partido:
  - Línea de contexto en rojo (CSS color rojo, ej `#c00`): GRUPO (de observaciones, si aplica) |
    `division.nombreDivision` | fecha formateada | ASOCIACIÓN DE ...
  - Tabla con bordes negros gruesos (`border: 2px solid #000` en `table` y `td`), 4 filas
    replicando el layout original:
    - Fila 1: PARTIDO | equipoLocal | equipoVisitante | ARBITRO | nombre Central | asociación
    - Fila 2: ESTADIO | nombreSede (o "Sin sede asignada" si null) | LINEA UNO | nombre Línea 1
    - Fila 3: DIA | día+fecha formateada | HORA | hora | LINEA DOS | nombre Línea 2
    - Fila 4: CIUDAD | municipio de la sede | EMERGENTE | nombre Emergente
  - `page-break-inside: avoid` por bloque para que dompdf no corte un partido entre dos páginas.
- Footer igual al de `acta-designacion.blade.php`.

---

## 8. Fases de implementación

### Fase 0 - Dependencia y datos de referencia
1. `composer require phpoffice/phpword`
2. Confirmar contra un .docx de muestra real (pedir al usuario un archivo de ejemplo si no está ya
   disponible) los formatos exactos de fecha/hora y los nombres de rol en `roles_partido` antes de
   codificar el parser y la vista PDF. Reduce el riesgo de la heurística de fecha descrita en la
   sección 2.

### Fase 1 - Parser + matching + preview (sin crear nada en BD todavía)
Archivos a crear:
- `app/Services/Importacion/PartidoWordParser.php`
- `app/Services/Importacion/MatchingTextoService.php`
- `app/Http/Controllers/Designacion/ImportacionDesignacionesController.php` (métodos `mostrar`,
  `procesar`, `revisar`, `cancelar`; sin `confirmar` todavía)
- `resources/views/designaciones/importar.blade.php`
- `resources/js/designaciones/importar-designaciones.js`
- `resources/css/designaciones/importar-designaciones.css` (o reúso de `designaciones.css`,
  decidir tras revisar su contenido actual)
- Rutas nuevas en `routes/web.php` (excepto `confirmar`)
- Registrar CSS/JS nuevos en `vite.config.js` (bloque input, siguiendo el mismo patrón que las
  demás páginas del módulo)
- Enlace "Importar desde Word" en `resources/views/designaciones/index.blade.php`

Criterio de aceptación: subir un .docx real, ver la tabla de preview con todos los partidos
detectados, divisiones/sedes matcheadas o marcadas en rojo/ámbar según corresponda, sin que se cree
ningún `Partido` en BD todavía.

### Fase 2 - Creación en lote
Archivos a crear:
- `app/Services/Importacion/ImportacionPartidosService.php`
- Agregar método `confirmar` al `ImportacionDesignacionesController` + ruta
  `designaciones.importar.confirmar`

Criterio de aceptación: confirmar el preview crea todos los partidos incluidos en estado borrador,
con slots generados (verificar en `slots_designacion`) e historial (`historial_designacion` con
`tipoAccion = partido_creado`), y aparecen en `designaciones.index?torneo=X`.

### Fase 3 - Exportación PDF
Archivos a crear/tocar:
- `app/Http/Controllers/Designacion/ExportacionPdfController.php` (mover `generarActa` desde
  `DesignacionController` + nuevo `generarListado`)
- `resources/views/pdf/listado-partidos.blade.php`
- Actualizar ruta `designaciones.partido.acta` para apuntar al nuevo controller
- Nueva ruta `designaciones.listado.pdf` (o nombre similar) para `generarListado`
- Botón "Exportar PDF" en `resources/views/designaciones/partidos-torneo.blade.php`

Criterio de aceptación: con partidos ya designados (algunos con Central+Líneas, otros
incompletos), el PDF generado muestra los nombres reales de árbitros en las columnas
correspondientes y deja en blanco los roles sin designar, visualmente fiel al Word original.

---

## 9. Plan de verificación end-to-end

1. Preparación: tener un Torneo activo con al menos 3 `DivisionTorneo` — dos que matchearán
   distintos bloques del mismo Word (ej. "PRIMERA C" y "SEGUNDA B", confirmando que el archivo real
   trae más de una categoría) y una que exista en el sistema pero no aparezca en el Word (para
   verificar que no se fuerza matching erróneo), más al menos una categoría del Word que
   deliberadamente NO tenga `DivisionTorneo` creada (para probar el caso de error). También al
   menos 1 `SedeTorneo` que matchee parcialmente (algunos partidos del Word usan una sede que
   existe, otros usan una que no, para probar la advertencia de sede nula).
2. Subida: ir a `/designaciones/importar`, seleccionar únicamente el torneo (sin seleccionar
   división — el formulario de subida no pide división, ver 2.1), subir el .docx de muestra, elegir
   un formato default (ej. "Terna"). Verificar que redirige al preview mostrando N partidos
   detectados (contar manualmente contra el Word para confirmar que el número de tablas parseadas
   coincide con el número real de partidos del documento), agrupados visualmente por
   división/categoría, y que el desglose por división del resumen coincide con las categorías
   reales presentes en el Word (ej. si el archivo trae "PRIMERA C" y "SEGUNDA B", ambas deben
   aparecer como grupos separados en el preview, cada una con sus propios partidos correctamente
   matcheados a su `DivisionTorneo` respectiva).
3. Preview, casos de error/advertencia: confirmar visualmente que el partido cuya categoría no
   matchea ninguna división queda marcado en rojo con el checkbox "incluir" desmarcado/bloqueado;
   que el partido cuya sede no matchea queda en ámbar pero incluido (con "Sin sede"); y que los
   campos de fecha/hora se muestran ya parseados correctamente (contrastar contra 2-3 partidos del
   Word a ojo).
4. Corrección manual: en la fila con error de división, seleccionar manualmente la división
   correcta desde el select y reenviar (`revisar`) - confirmar que el error desaparece y el
   checkbox se puede marcar.
5. Confirmación: pulsar "Confirmar importación", verificar mensaje de éxito con el conteo, y que
   redirige a `designaciones.index?torneo=X` mostrando los partidos recién creados en estado
   Borrador.
6. Verificación en BD (vía tinker o consulta directa): `SELECT COUNT(*) FROM partidos WHERE
   idTorneo = X` coincide con el número de filas confirmadas; `SELECT observaciones FROM partidos
   WHERE idTorneo = X` contiene el texto GRUPO N esperado por partido; `slots_designacion` tiene
   registros para cada partido nuevo acorde al formato elegido; `historial_designacion` tiene una
   entrada `partido_creado` por cada partido nuevo.
7. Designación de árbitros: entrar a `designaciones.show` de 2-3 partidos importados, asignar
   árbitros a los roles (Central, Línea 1, etc.) usando el flujo M04 existente sin ninguna
   modificación - confirmar que funciona exactamente igual que con un partido creado manualmente
   (prueba de que `crearPartido()` dejó todo en el mismo estado que el flujo manual).
8. Exportación PDF: ir a la vista de partidos del torneo, pulsar "Exportar PDF", abrir el PDF
   descargado y verificar que el bloque de cada partido muestra GRUPO/CATEGORÍA/FECHA/ASOCIACIÓN en
   rojo arriba igual que el Word, que la tabla con bordes muestra PARTIDO/ESTADIO/DIA-HORA/CIUDAD a
   la izquierda, y que las columnas ARBITRO/LINEA UNO/LINEA DOS/EMERGENTE muestran los nombres
   reales de los árbitros asignados en el paso 7, quedando en blanco para los partidos aún sin
   designar completo.
9. Caso negativo, archivo inválido: subir un archivo .pdf o .doc (no .docx) a
   `/designaciones/importar` y confirmar que la validación `mimes:docx` rechaza con mensaje de
   error claro, sin romper.
10. Multi-tenancy: repetir el paso 2 con un usuario de otro colegio y confirmar que solo ve sus
    propios torneos en el selector, y que un intento de manipular `idTorneo` por form tampering
    (Torneo de otro colegio) es rechazado por el `firstOrFail()` con `where('idColegio', ...)`.

---

## Riesgos / puntos a confirmar antes o durante la implementación

1. No hay un .docx de muestra real adjunto a este plan. El parser (sección 2) está diseñado sobre
   la descripción textual del formato, pero los formatos exactos de fecha ("09 JULIO 04/05", "3:00
   PM" vs "15:00", etc.) y los nombres exactos de rol en `roles_partido` deben confirmarse contra
   un archivo real antes de fijar las expresiones regulares y el `keyBy` de roles en el PDF.
   Recomendación: pedir al usuario el archivo de ejemplo (o generar uno de prueba con el formato
   descrito) antes de iniciar la Fase 0.
2. Driver de sesión: confirmar `config/session.php` (`SESSION_DRIVER` en `.env`) para asegurar que
   el array de partidos parseados (potencialmente varias decenas de KB) no exceda límites si el
   driver fuera `cookie` (poco probable dado que el proyecto usa Redis, pero se debe confirmar, no
   asumir).
3. Nombres de rol en `roles_partido`: verificar contra el seeder (`database/seeders`, ej.
   `RolPartidoSeeder.php`) antes de escribir la vista PDF - el plan asume Central, Línea 1, Línea
   2, Emergente pero deben confirmarse los nombres reales guardados.
4. Tamaño de `DesignacionController.php` (897 líneas, confirmado por conteo real) - ya está por
   encima del límite sugerido de 600-700; este plan explícitamente evita agregarle más
   responsabilidades (todo el importador va en su propio controller/servicios) y recomienda además
   extraer `generarActa` a un controller de exportación separado para no seguir creciendo ese
   archivo.
