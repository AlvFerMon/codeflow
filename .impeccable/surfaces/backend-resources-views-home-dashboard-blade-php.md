---
version: 1
slug: "backend-resources-views-home-dashboard-blade-php"
primary_target: "backend/resources/views/home/dashboard.blade.php"
related_targets: ["backend/resources/views/layouts/app.blade.php"]
---

# Surface brief: aplicación web de CodeFlow (rediseño completo)

## Scope and mode

Toda la web Blade autenticada (layout, inicio, proyectos, repositorios, ramas, commits, issues, pull
requests, actividad, avisos, perfil) más auth y la versión móvil. Modo: Operate. Pantalla que define el
rediseño: el inicio (dashboard).

## Audience and task

Estudiantes y gente que no se maneja con GitHub. Entran a ver qué ha cambiado, qué les toca revisar y a
subir o editar código sin terminal. Evitar: parecer un clon de GitHub, perder las animaciones de
front-animado (icon bar elástica, buscador, OTP, medidor de contraseña), exceso de color.

## Direction contract

THESIS: CodeFlow es la ficha de préstamo de la biblioteca del instituto: cada cambio queda sellado con
quién y cuándo, en columnas rayadas que se leen sin saber Git. Rechaza el dashboard gris de tarjetas
flotantes con métricas gigantes y neón sobre negro.

OWN-WORLD: cartulina fría `#F2F4FF` con fichas blancas y rayado de 1px `#C4C9E0`/`#E3E7F8`; tinta
`#101323`. Color solo como tinta de tampón: amatista por defecto, fucsia para lo que te toca o es nuevo,
verde azulado para fusionado/hecho, chartreuse casi nunca (resaltado de hoy en oscuro). Estado por forma
del sello: redondo hecho, rectangular pendiente, tachado cerrado. Atkinson Hyperlegible Next para todo;
Atkinson Hyperlegible Mono solo para hashes, fechas y números. Radios pequeños (4–8px), sin sombras
decorativas. Modo oscuro: ficha de noche `#0B0D1A` con rayado `#232842`.

STORY: entra, lee en una frase cuánto ha cambiado, ve cada repo como una ficha con sus últimos sellos y,
a la derecha, lo que le toca hacer; abre la ficha y sigue trabajando.

FIRST VIEWPORT: barra superior blanca de 64px (logo, navegación de siempre con indicador elástico,
buscador, botón fucsia «Nuevo proyecto», avatar). Debajo, dos columnas: izquierda «Hola, Laura» y una
frase de recuento en peso, después una ficha por repositorio (datos a la izquierda, columna rayada de
sellos de fecha a la derecha); derecha 380px «Te toca» con sellos de forma por PR/issue y estado vacío
explicado.

FORM: Ficha de préstamo, candidato 6 de mi lista ordenada; seed: ce9364eaf6d1650d.

FINISH: unreviewed and undocumented is unfinished; this build ends with the finish review, the verdict,
DESIGN.md, and every shipping raster carrying its provenance

## Raises kept from the round

- Raíl de emisión: el estado nunca depende solo del color (forma del sello).
- Anuario de diseño: una rejilla de reglas de 1px; nada de tarjetas flotantes con sombra.
- Horario en diapositivas: pocos tamaños de texto; jerarquía por peso y mayúscula.
- Funda de catálogo: números visibles y estables (#12, e8b1195).
