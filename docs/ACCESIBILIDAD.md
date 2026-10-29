# Preferencias de accesibilidad

Cada usuario elige en **Mi perfil › Accesibilidad y apariencia** (o por API) cómo quiere ver CodeFlow. Se guardan en
la cuenta (`users.preferences`, JSON), así que se aplican en cualquier navegador y las apps pueden leerlas.

| Clave | Valores | Qué hace en la web |
|---|---|---|
| `theme` | `system` · `light` · `dark` | Tema. El selector del menú también lo guarda en la cuenta |
| `font_size` | `normal` · `large` (112,5 %) · `larger` (125 %) | Escala toda la interfaz (Tailwind usa `rem`) |
| `high_contrast` | `true` / `false` | Textos secundarios y bordes más marcados; foco más visible |
| `reduce_motion` | `true` / `false` | Sin animaciones ni transiciones aunque el sistema no lo pida |
| `underline_links` | `true` / `false` | Enlaces de texto siempre subrayados |
| `wrap_code` | `true` / `false` | El código y los diffs saltan de línea en vez de desplazarse |

- Código: `backend/app/Support/Preferences.php`, `PreferencesController`, `resources/css/preferences.css`.
- El layout escribe atributos en `<html>`: `data-font-size`, `data-contrast="high"`, `data-motion="reduce"`,
  `data-links="underline"`, `data-wrap-code="true"`. **En el rediseño** basta con mantener
  `{!! \App\Support\Preferences::htmlAttributeString(auth()->user()) !!}` en la etiqueta `<html>` de cada layout
  y adaptar `preferences.css` si cambian las clases.

## API

| Método | Ruta | |
|---|---|---|
| GET | `/api/me/preferences` | Preferencias completas (con valores por defecto) |
| PATCH | `/api/me/preferences` | Cambia solo lo que se envía, p. ej. `{ "font_size": "large" }` |

`GET /api/me` también las incluye en `preferences`. No aparecen en los datos de otros usuarios.
