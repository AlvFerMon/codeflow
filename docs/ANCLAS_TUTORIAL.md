# Anclas del tutorial de iniciación (`data-tour`)

El tutorial se engancha a los elementos de la web con el atributo `data-tour`. Estos nombres son estables:
si se rediseña una pantalla, el ancla se conserva aunque cambien las clases o el texto.

Selector: `document.querySelector('[data-tour="nombre"]')`.

## Cabecera y navegación (todas las páginas con sesión)

| Ancla | Elemento | Archivo |
|---|---|---|
| `nav-home` | Pestaña «Inicio» de la barra de navegación (escritorio) | `components/icon-bar.blade.php` |
| `nav-projects` | Pestaña «Proyectos» | ídem |
| `nav-explore` | Pestaña «Explorar» (no existe en teléfonos) | ídem |
| `nav-activity` | Pestaña «Actividad» | ídem |
| `nav-notifications` | Pestaña «Notificaciones» | ídem |
| `buscar` | Buscador de repositorios (Ctrl/⌘ + K) | `layouts/app.blade.php` |
| `nuevo-proyecto` | Botón «Nuevo» (solo si puede crear proyectos) | ídem |
| `avisos` | Campana de notificaciones | ídem |
| `menu-usuario` | Menú del usuario (perfil, tema, cerrar sesión) | ídem |

Las anclas `nav-*` salen del nombre de la ruta (`home`, `projects.index` → `projects`…), así que una
pestaña nueva tiene su ancla automáticamente.

## Inicio (`home/dashboard.blade.php`)

| Ancla | Elemento |
|---|---|
| `resumen` | Frase con el recuento de repositorios, pull requests, issues y cambios de hoy |
| `ficha-repositorio` | Primera ficha de repositorio (o el estado vacío si no hay ninguno) |
| `sellos` | Columna de sellos de fecha de la primera ficha: los últimos cambios |
| `te-toca` | Columna «Te toca»: revisiones pedidas, issues asignadas o urgentes y lo fusionado |

## Repositorio (`repositories/_header.blade.php`, `repositories/show.blade.php`)

| Ancla | Elemento |
|---|---|
| `pestana-codigo` | Pestaña «Código» |
| `pestana-issues` | Pestaña «Issues» |
| `pestana-pulls` | Pestaña «Pull requests» |
| `pestana-commits` | Pestaña «Commits» |
| `pestana-ramas` | Pestaña «Ramas» |
| `selector-rama` | Selector de rama |
| `subir-archivos` | Botón «Subir archivos o carpetas» (solo con permiso de escritura) |

## Añadir anclas nuevas

- En pestañas (`<x-tabs>`), añade la clave `'tour' => 'nombre'` al elemento.
- En cualquier otro elemento o componente, pon `data-tour="nombre"`; los componentes Blade lo pasan a su
  elemento raíz.
- Nombres en minúsculas, en español y con guiones. No los reutilices para estilos ni para tests.
