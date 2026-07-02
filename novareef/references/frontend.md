# Frontend — CSS + Vanilla JS + Blade en NovaReef

## Arquitectura visual (estado actual)

- **Blade** maneja layouts, navegación y todas las páginas — es server-rendered.
- **CSS puro con variables custom** para todo el styling del panel usuario. Tailwind v4 está instalado pero **no se usan clases Tailwind en las vistas** — solo se usa como base/reset.
- **Vanilla JS modular**: un archivo por sección (`arbitros.js`, `torneos.js`, `designaciones.js`, etc.). No hay framework JS en producción todavía.
- **Vue 3** está instalado (`vue ^3.5.34`, `@vitejs/plugin-vue`) pero **aún no hay archivos `.vue` en el proyecto**. Se usará cuando se requieran componentes complejos.
- **Dark theme permanente.** No hay toggle. Implementado via CSS variables, no clases `dark:`.

## Layout base real

```blade
{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="es">  {{-- sin class="dark" — el dark es permanente via CSS --}}
<head>
    <meta charset="utf-8">
    <title>@yield('titulo', 'Panel') — NovaReef</title>
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body>
    <header class="navbar" id="navbar">...</header>
    <div class="app-layout">
        <aside class="sidebar">...</aside>
        <main class="main">
            @yield('contenido')
        </main>
    </div>
    @stack('scripts')
</body>
</html>
```

**Puntos clave:**
- `@yield('titulo')` para el título de la pestaña
- `@yield('seccion')` para el nombre de sección en el navbar
- `@yield('contenido')` para el contenido principal
- `@stack('styles')` para CSS adicional por módulo
- `@stack('scripts')` para JS adicional por módulo
- Font: **Inter** desde Bunny Fonts

## Paleta de colores real (CSS variables en `app.css`)

```css
:root {
    --bg-base:          #020617;   /* fondo de página */
    --bg-surface:       #0f172a;   /* fondo de secciones */
    --bg-card:          #1e293b;   /* fondo de cards */
    --bg-card-hover:    #263143;

    --border:           rgba(255, 255, 255, 0.06);
    --border-hover:     rgba(255, 255, 255, 0.11);
    --border-accent:    rgba(79, 142, 247, 0.22);

    --text-primary:     #f8fafc;
    --text-secondary:   #94a3b8;
    --text-muted:       #475569;

    --accent:           #4f8ef7;   /* azul primario — NO cambiar a verde */
    --accent-light:     #7aa8f9;
    --accent-bg:        rgba(79, 142, 247, 0.08);
    --accent-glow:      rgba(79, 142, 247, 0.22);

    --danger:           #ef4444;
    --danger-bg:        rgba(239, 68, 68, 0.07);

    --radius-sm:        6px;
    --radius-md:        10px;
    --radius-lg:        14px;
    --radius-xl:        20px;

    --navbar-h:         64px;
    --font:             'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}
```

**⚠️ No usar colores hardcodeados en vistas nuevas.** Siempre referenciar las variables.

## Panel Admin — paleta separada (`resources/css/admin/admin.css`)

```css
--primary:      #4f8ef7
--bg-navbar:    #1a1f2e
--bg-body:      #0f1117
--bg-card:      #131927
--text-bright:  #e2e8f0
--text:         #8892a4
--border-color: rgba(255,255,255,0.06)
```

## Estructura de archivos JS y CSS

```
resources/
├── css/
│   ├── app.css                    ← variables + reset + componentes globales
│   ├── vendor/fontawesome-fonts.css  ← @font-face manual (evita rutas Windows)
│   ├── auth/login.css
│   ├── arbitros/arbitros.css
│   ├── torneos/torneos.css
│   ├── designaciones/designaciones.css
│   ├── colegios/colegios.css
│   └── admin/admin.css
└── js/
    ├── app.js                     ← bootstrap global: Choices.js, Flatpickr, SweetAlert2
    ├── bootstrap.js               ← Axios config
    ├── auth/login.js
    ├── arbitros/arbitros.js
    ├── torneos/torneos.js
    ├── designaciones/designaciones.js
    ├── colegios/colegios.js
    ├── welcome.js
    └── admin/admin.js
```

Cada módulo tiene su propio par CSS/JS. Se cargan con `@vite()` en `@push('styles')` y `@push('scripts')`.

## Librerías JS globales (inicializadas en `app.js`)

| Librería | Versión | Global | Uso |
|---|---|---|---|
| **Choices.js** | ^11.2.3 | `window.Choices` | Selects con búsqueda y dark theme |
| **Flatpickr** | ^4.6.13 | `window.flatpickr` | Date/time pickers |
| **SweetAlert2** | ^11.x | `window.Swal` | Modales de confirmación y toasts |
| **Font Awesome** | ^7.2.0 | via CSS | Iconos (instalado via npm, NO CDN) |
| **Laravel Echo** | ^2.3.7 | `window.Echo` | WebSockets con Reverb |
| **Pusher JS** | ^8.5.0 | — | Cliente Pusher para Echo |

## Helpers globales en `app.js`

### `window.novaAlert` — sistema de notificaciones unificado

```js
// Toast verde 3s, sin botón
window.novaAlert.success('Árbitro guardado correctamente');

// Modal de error rojo
window.novaAlert.error('No se pudo guardar');

// Confirmación destructiva
window.novaAlert.confirm({
    titulo: '¿Archivar árbitro?',
    texto: 'Podrás restaurarlo después',
    icono: 'warning',
    confirmColor: '#ef4444',
    confirmarTexto: 'Sí, archivar',
    iconColor: '#f59e0b',
}).then(result => {
    if (result.isConfirmed) { ... }
});
```

### `window.initNovaSelects(container?)` — inicialización de Choices y Flatpickr

Inicializa todos los `select[data-nova-select]` e `input[data-nova-date]` dentro del container dado (default: `document`). Idempotente — ignora elementos ya inicializados.

```js
// Al abrir un modal con contenido dinámico:
window.initNovaSelects(document.getElementById('mi-modal'));
```

## Choices.js — patrones de uso

```html
<!-- Select básico -->
<select name="idTorneo" data-nova-select data-placeholder="Selecciona torneo">
    <option value="">Selecciona torneo</option>
    ...
</select>

<!-- Select con búsqueda (más de ~10 opciones) -->
<select name="idArbitro" data-nova-select data-searchable="true" data-placeholder="Buscar árbitro">
    ...
</select>
```

**Para selects dinámicos** (poblados por AJAX), hay que destruir y recrear la instancia:

```js
// Guardar referencia al crear
el._choicesInstance = new window.Choices(el, { ... });
el.dataset.choicesInit = '1';

// Antes de repoblar
if (el._choicesInstance) {
    el._choicesInstance.destroy();
    el._choicesInstance = null;
    delete el.dataset.choicesInit;
}
el.innerHTML = '<option value="">...</option>';

// Recrear después
el._choicesInstance = new window.Choices(el, { ... });
el.dataset.choicesInit = '1';
```

**⚠️ NUNCA usar `<select>` nativo sin Choices.js para dropdowns estilizados.** En Windows, el select nativo siempre renderiza con fondo blanco independientemente del CSS.

## Flatpickr — patrones de uso

```html
<!-- Date picker -->
<input type="text" name="fechaPartido" data-nova-date>

<!-- Time picker -->
<input type="text" name="horaPartido" data-nova-date
       data-enable-time="true" data-no-calendar="true"
       data-date-format="H:i" data-alt-format="H:i">
```

**Siempre `type="text"`**, nunca `type="date"` — el nativo no se puede estilizar consistentemente.

Configuración: `dateFormat: 'Y-m-d'` (backend Laravel) + `altFormat: 'd/m/Y'` (display usuario).

## Imports CSS en `app.css`

```css
@import "tailwindcss";
@import "@fortawesome/fontawesome-free/css/fontawesome.min.css";
@import "./vendor/fontawesome-fonts.css";

/* Vendors en @layer para que nuestros overrides siempre ganen */
@layer vendor {
    @import "choices.js/public/assets/styles/choices.min.css";
    @import "flatpickr/dist/flatpickr.min.css";
}
```

**Por qué `@layer vendor`:** Choices.js v11 usa CSS Custom Properties (`var(--choices-bg-color-dropdown, #fff)`). Sin el layer, sus reglas compiten con las nuestras. Dentro del layer siempre pierde ante estilos sin layer.

## Font Awesome 7

Instalado via npm. **No usar CDN** — causa problemas de CORS y versiones mixtas.

```css
/* ✅ Correcto */
@import "@fortawesome/fontawesome-free/css/fontawesome.min.css";

/* Los @font-face van en vendor/fontawesome-fonts.css (rutas relativas manuales)
   para evitar el bug de Vite con rutas absolutas en Windows */
```

## Vite — config real

Cada par CSS/JS se declara explícitamente en `vite.config.js`. Vue y Tailwind están configurados como plugins.

```js
// vite.config.js
plugins: [laravel({ input: [...] }), vue(), tailwindcss()]
resolve: { alias: { vue: 'vue/dist/vue.esm-bundler.js' } }
```

## Vue 3 — 🚧 planificado

Vue está instalado y configurado en Vite pero **no hay componentes `.vue` todavía**. Cuando se use:

- `<script setup>` siempre (Composition API). No Options API en código nuevo.
- Props con nombres del dominio en camelCase: `colegioId`, `idArbitro`.
- Eventos en kebab-case: `emit('arbitro-seleccionado', arbitro)`.
- Blade sirve el div contenedor con data-attributes; Vue se monta sobre él.

```blade
<div id="arbitro-table" data-colegio="{{ auth()->user()->idColegio }}"></div>

@push('scripts')
<script type="module">
    import { createApp } from 'vue';
    import ArbitroTable from '@/components/arbitros/ArbitroTable.vue';
    const el = document.getElementById('arbitro-table');
    createApp(ArbitroTable, { colegioId: el.dataset.colegio }).mount(el);
</script>
@endpush
```

## Internacionalización

- UI en **español** únicamente.
- Fechas: formato `dd/mm/yyyy` (Colombia) en display; `yyyy-mm-dd` en valores de formulario (Laravel).
- Moneda: `COP` con formato `$ 1.234.567` (punto como separador de miles).
- Flatpickr localizado en español: `flatpickr.localize(Spanish)` en `app.js`.

## Checklist visual antes de cerrar una vista

- [ ] Fondo usa `var(--bg-base)` o `var(--bg-surface)` — no colores hardcodeados
- [ ] Texto usa `var(--text-primary)` o `var(--text-secondary)`
- [ ] Acento usa `var(--accent)` (#4f8ef7 azul, no verde)
- [ ] Selects con Choices.js (`data-nova-select`) — no selects nativos
- [ ] Fecha/hora con Flatpickr (`data-nova-date`) — no `type="date"`
- [ ] Botones destructivos con `window.novaAlert.confirm()` — no `confirm()` nativo
- [ ] Flash messages del servidor disparan `window.novaAlert.success/error` automáticamente (desde `layouts/app.blade.php`)
- [ ] Strings en español
- [ ] Fechas en formato `dd/mm/yyyy` en display
- [ ] Sin `console.log` olvidados
