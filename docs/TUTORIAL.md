# Tutorial de bienvenida (onboarding)

Al entrar por primera vez (con el correo verificado) se muestra una **visita guiada**, y en el inicio aparece la lista
**«Primeros pasos»**. La visita se puede saltar (pide confirmación) y se vuelve a ver desde el menú de usuario ›
«Ver el tutorial otra vez». Las cuentas que ya existían antes de esta función no ven la visita.

## Cómo funciona

| Pieza | Archivo |
|---|---|
| Pasos de la visita y tareas de la lista | `backend/config/onboarding.php` |
| Lógica y estado (`users.onboarding`, JSON) | `backend/app/Services/OnboardingService.php` |
| Rutas (API y web, mismo controlador) | `backend/app/Http/Controllers/OnboardingController.php` |
| Visita guiada (JS sin dependencias) | `backend/resources/js/onboarding.js` + `resources/css/onboarding.css` |
| Datos para el JS | `backend/resources/views/layouts/_onboarding.blade.php` |
| Lista «Primeros pasos» | `backend/resources/views/components/onboarding-checklist.blade.php` |

- Las tareas de la lista **se marcan solas** al hacer la acción de verdad (registro de actividad: `project.created`,
  `commit.created`…; la app de escritorio, la CLI o Android por el nombre de su token). Lo detectado se guarda.
- **Proyecto de práctica**: crea un proyecto con repositorio, una rama con un cambio, una issue y un PR abierto
  para practicar. Lo que se haga en él no cuenta para la lista.
- La lista se puede **ocultar 7 días**; vuelve mientras no esté completa (la tarea de instalar una app es opcional).

## Para el rediseño del front

Solo hay que conservar:

1. `@include('layouts._onboarding')` en el layout (para usuarios verificados).
2. Los atributos **`data-tour`** de los elementos que se resaltan. La lista completa de anclas está en
   [`ANCLAS_TUTORIAL.md`](ANCLAS_TUTORIAL.md); la visita usa `te-toca`, `nav-projects`, `nuevo-proyecto`, `avisos`,
   `nav-downloads`, `menu-usuario` y `primeros-pasos` (la lista «Primeros pasos»).

   Si un elemento no existe o no se ve (p. ej. en el móvil), ese paso se muestra centrado.
3. `<x-onboarding-checklist />` donde se quiera la lista (ahora, en el inicio), o pintarla con los datos de la API.
4. El formulario `POST route('onboarding.restart')` («Ver el tutorial otra vez»).

El aspecto se cambia en `resources/css/onboarding.css` (clases `cf-tour-*`).

## API (Android, escritorio u otro front)

Todas con `auth:sanctum` y correo verificado. En la web existen las mismas rutas bajo `/onboarding/…` (sesión + CSRF).

| Método | Ruta | Qué hace |
|---|---|---|
| GET | `/api/me/onboarding` | Estado completo (ver abajo) |
| POST | `/api/me/onboarding/tour/progress` | `{ "step": 3 }` guarda en qué paso va |
| POST | `/api/me/onboarding/tour/complete` | Visita terminada |
| POST | `/api/me/onboarding/tour/skip` | Visita saltada |
| POST | `/api/me/onboarding/restart` | Vuelve a empezar la visita y muestra la lista |
| POST | `/api/me/onboarding/checklist/hide` | Oculta la lista unos días |
| POST | `/api/me/onboarding/checklist/show` | La vuelve a mostrar |
| POST | `/api/me/onboarding/sample-project` | Crea (una vez) el proyecto de práctica |

```json
{
  "tour": { "status": "pending", "show": true, "step": 0,
            "steps": [{ "id": "welcome", "title": "…", "text": "…", "anchor": null }] },
  "checklist": { "visible": true, "completed": false, "done": 2, "total": 8,
                 "required_done": 2, "required_total": 7, "hidden_until": null,
                 "items": [{ "id": "create_project", "title": "Crea un proyecto", "text": "…",
                             "url": "https://…/projects/create", "optional": false,
                             "done": true, "done_at": "2026-10-30T10:00:00+01:00" }] },
  "sample_project": { "id": null, "url": null }
}
```
