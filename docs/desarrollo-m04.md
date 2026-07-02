# NovaReef — M04 Designaciones — Guía de desarrollo local

## Terminales requeridas en desarrollo

Para que el módulo de designaciones funcione completamente en local, se necesitan 3 terminales:

### Terminal 1 — Servidor PHP (XAMPP)
Apache ya corre en XAMPP. Acceder en:
```
http://localhost/novareef/public
```

### Terminal 2 — Workers de cola (Jobs)
```bash
php artisan queue:work --tries=3
```
Esto procesa las notificaciones de email y SMS de forma asíncrona.

### Terminal 3 — Servidor Reverb (WebSockets)
```bash
php artisan reverb:start
```
Esto habilita el broadcasting en tiempo real para actualizar cards sin recargar.

---

## Variables de entorno — M04

En `.env` deben estar configuradas:

```env
# Broadcasting (Reverb)
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=novareef
REVERB_APP_KEY=novareef-key
REVERB_APP_SECRET=novareef-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http

# Queue
QUEUE_CONNECTION=database

# SMS via Twilio (opcional — si no se configura, solo envía emails)
TWILIO_ACCOUNT_SID=
TWILIO_AUTH_TOKEN=
TWILIO_FROM=+1234567890
```

---

## Assets frontend

Después de cambios en JS/CSS:
```bash
npm run build    # producción
# o
npm run dev      # desarrollo con HMR
```

---

## Caché
```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

## Arquitectura M04 — Bloque 3

| Componente | Ubicación |
|---|---|
| State Machine | `app/StateMachines/PartidoStateMachine.php` |
| Optimistic Lock Exception | `app/Exceptions/OptimisticLockException.php` |
| Events | `app/Events/` (3 eventos) |
| Jobs | `app/Jobs/` (6 jobs) |
| Mails | `app/Mail/` (5 mails) |
| Vistas email | `resources/views/emails/` |
| Controlador | `app/Http/Controllers/Designacion/DesignacionController.php` |
| Vistas | `resources/views/designaciones/` + `resources/views/mis-partidos/` |
| CSS | `resources/css/designaciones/designaciones.css` |
| JS | `resources/js/designaciones/designaciones.js` |

---

## Flujo de designación

1. Designador crea partido → `guardarPartido()` → historial `partido_creado`
2. Designador asigna árbitro → `asignarArbitro()` → historial `asignado` + `NotificarDesignacionJob`
3. Árbitro confirma → `confirmarDesignacion()` → historial `confirmado`
   - Si TODOS confirmaron → `PartidoStateMachine::transicionarCon('confirmado')`
4. Árbitro rechaza → `rechazarDesignacion()` → historial `rechazado` + `NotificarRechazoJob`
   - Si partido estaba `confirmado` → vuelve a `programado`
5. Cambio de estado → `cambiarEstadoPartido()` → `PartidoStateMachine` → historial + jobs secundarios

## Optimistic Locking

Toda operación sobre `partidos` verifica la columna `version`:
```sql
UPDATE partidos SET estadoPartido=?, version=version+1
WHERE idPartido=? AND version=?
-- Si 0 filas afectadas → OptimisticLockException → HTTP 409
```
