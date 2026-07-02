# NovaReef — Configuración del Scheduler en Windows

Laravel necesita que `php artisan schedule:run` se ejecute cada minuto.
En producción Linux esto se hace con cron; en Windows se usa el Programador de tareas.

---

## Pasos

### 1. Abrir el Programador de tareas

- Presiona `Win + S` → busca **"Programador de tareas"** → Abrir.

### 2. Crear tarea básica

En el panel derecho haz clic en **"Crear tarea básica..."**

### 3. Configurar la tarea

| Campo | Valor |
|---|---|
| **Nombre** | `NovaReef Scheduler` |
| **Descripción** | Ejecuta el scheduler de Laravel cada minuto |

### 4. Desencadenador

- Selecciona **"Diariamente"**
- En la pantalla siguiente marca **"Repetir la tarea cada: 1 minuto"** durante **"indefinidamente"**

> **Importante:** la opción de repetición está en *Propiedades avanzadas* del desencadenador,
> no en el asistente. Después de crear la tarea ábrela con doble clic → pestaña
> **Desencadenadores** → Editar → activa **"Repetir la tarea cada"** → `1 minuto`.

### 5. Acción

- Acción: **Iniciar un programa**
- Programa o script:

```
C:\xampp\php\php.exe
```

- Agregar argumentos:

```
C:\xampp\htdocs\novareef\artisan schedule:run >> C:\xampp\htdocs\novareef\storage\logs\scheduler.log 2>&1
```

- Iniciar en (directorio de trabajo):

```
C:\xampp\htdocs\novareef
```

### 6. Guardar

Haz clic en **Finalizar**. Windows puede pedir la contraseña del usuario actual.

---

## Verificación manual

Ejecuta en CMD o PowerShell:

```powershell
php C:\xampp\htdocs\novareef\artisan schedule:run
```

Debes ver algo como:

```
Running scheduled command: novareef:marcar-criticos
```

(o ninguna salida si ningún comando está programado para ese minuto exacto).

---

## Comandos programados

| Comando | Horario | Descripción |
|---|---|---|
| `novareef:marcar-criticos` | Diario 06:00 | Marca partidos del día sin designaciones completas como CRÍTICOS |
| `novareef:habilitar-disponibilidad` | Lunes 00:01 | Limpia disponibilidades de semanas anteriores |

---

## Log del scheduler

Los resultados se almacenan en:

```
storage\logs\scheduler.log
```

También puedes ver la salida directamente en `storage/logs/laravel.log`
ya que los comandos usan `Log::info()` y `Log::warning()`.
