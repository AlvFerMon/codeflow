# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

Estudiantes y personas que no se manejan bien con GitHub: gente que empieza a trabajar en equipo con
código y a la que Git, las ramas o las pull requests le resultan intimidantes. Entran para subir su
código, ver qué ha cambiado, abrir issues y revisar el trabajo de sus compañeros sin tener que aprender
la línea de comandos. Es además un TFG de 2.º DAM, así que también lo verá un tribunal.

## Product Purpose

Plataforma colaborativa inspirada en GitHub/GitLab con todo el flujo dentro de la web: proyectos,
miembros con roles, repositorios con control de versiones propio, ramas, commits, issues, pull requests
con revisión y fusión, actividad y notificaciones. El éxito es que alguien sin experiencia en Git pueda
colaborar en un repositorio desde el navegador y entienda lo que está pasando.

## Positioning

El control de versiones lo implementa CodeFlow mismo (no Git) y se maneja entero desde la web, sin
terminal: subir archivos o carpetas arrastrándolos, editar archivos en el navegador, ramas y fusiones con
botones. El contenido de cada repositorio va cifrado con una clave propia por repositorio.

## Operating Context

- Web Blade + Tailwind (Laravel 13) y una app Android (Kotlin, Compose) que usa la misma API REST.
- Despliegue con Docker en servidor propio y en Vercel (PHP serverless + Postgres de Supabase).
- Revisión opcional de pull requests con IA (Ollama).
- Registro con verificación del email mediante un código de 6 dígitos.
- Los visitantes sin sesión pueden explorar los repositorios públicos.

## Capabilities and Constraints

- Roles por proyecto: owner, collaborator y observer, más un administrador global.
- Textos de interfaz en español; identificadores en inglés.
- Modo claro y modo oscuro (preferencia guardada o la del sistema).
- La web en móvil exige sesión (`RequireLoginOnPhones`) y tiene un layout móvil propio.
- Límites de subida configurables (50 MB por archivo, 500 MB por subida, 1000 archivos por defecto).
- No añadir dependencias sin necesidad; las fuentes se autoalojan (sin CDNs externos).

## Brand Commitments

- Nombre: CodeFlow.
- Logo y marca: `docs/branding/` (logo claro/oscuro, isotipo, iconos de app). Se conservan.
- Paleta de marca, que se conserva: fucsia `#FF3366`, verde azulado `#2EC4B6`, chartreuse `#CAFE48`,
  amatista `#9448BC`, sobre blanco fantasma `#F2F4FF` y negro `#000501`.

## Evidence on Hand

- Imagen de presentación: `docs/branding/codeflow-presentacion.png`.
- Documentación del TFG: `docs/ARQUITECTURA.md`, `docs/DESPLIEGUE.md`, `docs/AUDITORIA_UI_RENDIMIENTO.xlsx`.
- No hay testimonios, clientes ni métricas de uso reales: no inventarlos.

## Product Principles

1. Que se entienda sin saber Git: cada acción dice lo que hace en lenguaje llano.
2. Todo desde el navegador: nada debe obligar a usar la terminal.
3. El estado siempre visible: en qué rama estás, qué ha cambiado, quién lo revisa.
4. Mismas reglas en web, API y Android: la web no inventa comportamientos propios.
