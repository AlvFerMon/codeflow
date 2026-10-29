---
name: CodeFlow
description: Colaboración en código desde el navegador, registrada como la ficha de préstamo de una biblioteca.
colors:
  cartulina: "#F2F4FF"
  ficha: "#FFFFFF"
  tinta: "#101323"
  tinta-titulo: "#0B0D1A"
  tinta-fuerte: "#232842"
  tinta-media: "#4B5170"
  tinta-suave: "#656B8A"
  regla: "#C4C9E0"
  regla-interior: "#DDE1F2"
  rayado: "#E3E7F8"
  fucsia-accion: "#DB1A4E"
  fucsia-accion-hover: "#B8123F"
  fucsia-marca: "#FF3366"
  amatista: "#9448BC"
  amatista-hover: "#7B3A9D"
  verde-sello: "#0E7A70"
  verde-marca: "#2EC4B6"
  chartreuse: "#CAFE48"
  chartreuse-texto: "#486000"
  noche-fondo: "#0B0D1A"
  noche-ficha: "#161A2E"
  noche-regla: "#232842"
  noche-rayado: "#1D2238"
  noche-tinta: "#F0F2FB"
  noche-amatista: "#CDA2EA"
  noche-fucsia: "#FF6A8C"
  noche-verde: "#45CFC2"
typography:
  display:
    fontFamily: "Atkinson Hyperlegible Next, ui-sans-serif, system-ui, sans-serif"
    fontSize: "3rem"
    fontWeight: 800
    lineHeight: 1.1
    letterSpacing: "-0.025em"
  headline:
    fontFamily: "Atkinson Hyperlegible Next, ui-sans-serif, system-ui, sans-serif"
    fontSize: "1.875rem"
    fontWeight: 800
    lineHeight: 1.2
    letterSpacing: "-0.025em"
  title:
    fontFamily: "Atkinson Hyperlegible Next, ui-sans-serif, system-ui, sans-serif"
    fontSize: "1.125rem"
    fontWeight: 800
    lineHeight: 1.25
  body:
    fontFamily: "Atkinson Hyperlegible Next, ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.875rem"
    fontWeight: 400
    lineHeight: 1.43
  lead:
    fontFamily: "Atkinson Hyperlegible Next, ui-sans-serif, system-ui, sans-serif"
    fontSize: "1rem"
    fontWeight: 400
    lineHeight: 1.5
  label:
    fontFamily: "Atkinson Hyperlegible Next, ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.6875rem"
    fontWeight: 700
    lineHeight: 1.45
    letterSpacing: "0.05em"
  sello:
    fontFamily: "Atkinson Hyperlegible Mono, ui-monospace, monospace"
    fontSize: "0.6875rem"
    fontWeight: 700
    lineHeight: 1.45
    fontFeature: "tnum"
  mono:
    fontFamily: "Atkinson Hyperlegible Mono, ui-monospace, monospace"
    fontSize: "0.875rem"
    fontWeight: 400
    lineHeight: 1.43
    fontFeature: "tnum"
rounded:
  sello: "3px"
  md: "4px"
  lg: "6px"
  xl: "8px"
  full: "999px"
spacing:
  fila: "36px"
  ficha: "20px"
  pila: "20px"
  columnas: "40px"
  seccion: "32px"
components:
  button-primary:
    backgroundColor: "{colors.fucsia-accion}"
    textColor: "{colors.ficha}"
    typography: "{typography.body}"
    rounded: "{rounded.md}"
    padding: "6px 12px"
  button-primary-hover:
    backgroundColor: "{colors.fucsia-accion-hover}"
  button-secondary:
    backgroundColor: "{colors.ficha}"
    textColor: "{colors.tinta-fuerte}"
    rounded: "{rounded.md}"
    padding: "6px 12px"
  button-ghost:
    textColor: "{colors.tinta-media}"
    rounded: "{rounded.md}"
    padding: "6px 12px"
  button-amethyst:
    backgroundColor: "{colors.amatista}"
    textColor: "{colors.ficha}"
    rounded: "{rounded.md}"
    padding: "6px 12px"
  button-success:
    backgroundColor: "{colors.verde-sello}"
    textColor: "{colors.ficha}"
    rounded: "{rounded.md}"
    padding: "6px 12px"
  button-danger:
    backgroundColor: "{colors.ficha}"
    textColor: "{colors.fucsia-accion-hover}"
    rounded: "{rounded.md}"
    padding: "6px 12px"
  ficha:
    backgroundColor: "{colors.ficha}"
    rounded: "{rounded.lg}"
    padding: "{spacing.ficha}"
  sello:
    textColor: "{colors.amatista}"
    typography: "{typography.sello}"
    rounded: "{rounded.sello}"
    padding: "1px 6px"
  sello-nuevo:
    textColor: "{colors.fucsia-accion}"
  sello-hecho:
    textColor: "{colors.verde-sello}"
    rounded: "{rounded.full}"
  sello-cerrado:
    textColor: "{colors.tinta-suave}"
  sello-estado:
    textColor: "{colors.fucsia-accion}"
    rounded: "{rounded.lg}"
    size: "40px"
  tab-activa:
    textColor: "{colors.tinta}"
    padding: "8px 12px"
  input:
    backgroundColor: "{colors.ficha}"
    textColor: "{colors.tinta}"
    rounded: "{rounded.md}"
    padding: "8px 12px"
---

# Design System: CodeFlow

## Overview

**Creative North Star: "La ficha de préstamo"**

CodeFlow se lee como la ficha de préstamo de la biblioteca del instituto: una cartulina fría sobre la que se apoyan fichas blancas con reglas de 1px, y en cada ficha una columna rayada donde cada cambio queda sellado con quién lo hizo y cuándo. La información se ordena en filas y columnas que se entienden sin saber Git; el color no decora, es tinta de tampón que dice el estado de algo.

La densidad es de herramienta de trabajo: texto de 14px, filas de 36px, pocos tamaños de letra y la jerarquía hecha con peso (400 a 800) y con mayúsculas pequeñas en las etiquetas. Las superficies son planas y se separan por reglas, no por sombras; solo los menús desplegables y los controles animados heredados (barra de navegación elástica, buscador) flotan. El modo oscuro es una ficha de noche en azul tinta profundo, nunca negro puro.

El sistema rechaza explícitamente el dashboard gris de tarjetas flotantes con métricas gigantes y el neón sobre negro.

**Key Characteristics:**
- Cartulina fría de fondo, fichas blancas con borde de 1px y sin sombra.
- Columna rayada cada 36px con sellos de fecha en Atkinson Hyperlegible Mono.
- Estado comunicado por la forma del sello (redondo hecho, rectangular pendiente, tachado cerrado), además del color.
- Una sola familia tipográfica (Atkinson Hyperlegible Next) y su Mono solo para hashes, fechas y cifras.
- Radios pequeños (3–8px); profundidad casi nula.

## Colors

Neutros fríos de cartulina y tinta azulada, con tres tintas de tampón de marca y un chartreuse que casi nunca aparece.

### Primary
- **Fucsia de acción** (fucsia-accion): el relleno del botón principal («Nuevo», «Crear una cuenta», «Registrarse») y la tinta del sello de lo nuevo o lo que te toca. Al pasar el ratón baja a **Fucsia profundo** (fucsia-accion-hover), que también es el texto del botón de peligro.
- **Fucsia de marca** (fucsia-marca): el color de marca sin rebajar. Se usa donde no lleva texto encima: anillo de foco global, subrayado de la pestaña activa, punto de avisos sin leer, cursor de texto y casillas.

### Secondary
- **Amatista** (amatista): la tinta por defecto del sello de fecha, los enlaces de texto y su hover, el foco de los campos y el recuento resaltado de las pestañas. Hover de enlace en **Amatista profunda** (amatista-hover).

### Tertiary
- **Verde sello** (verde-sello): lo fusionado, aprobado, resuelto o público; botón de éxito. **Verde de marca** (verde-marca) queda para la marca y fondos tenues, no para texto sobre blanco.
- **Chartreuse** (chartreuse): resaltado excepcional. De noche es la tinta del sello de hoy; de día solo aparece como fondo tenue de la etiqueta de advertencia/admin, con su texto en **Chartreuse legible** (chartreuse-texto).

### Neutral
- **Cartulina** (cartulina): fondo de página en claro.
- **Ficha** (ficha): superficie de fichas, barra superior, pie y menús.
- **Tinta** (tinta): texto principal y tinta base; **Tinta de título** (tinta-titulo) para los h1 y títulos de ficha.
- **Tinta fuerte** (tinta-fuerte): texto de botones secundarios y de datos clave (rama, hash).
- **Tinta media** (tinta-media): texto secundario, descripciones y frases de recuento.
- **Tinta suave** (tinta-suave): metadatos, cabeceras de columna, autor del cambio y sello cerrado.
- **Regla** (regla): borde exterior de cada ficha, barra superior y pie, y divisiones mayores.
- **Regla interior** (regla-interior): divisiones dentro de una ficha (cabecera de tarjeta, filas de lista).
- **Rayado** (rayado): las líneas de la columna rayada.
- **Noche**: fondo (noche-fondo), ficha (noche-ficha), regla de ficha (noche-regla), rayado (noche-rayado) y tinta (noche-tinta). Las tintas de tampón se aclaran de noche: amatista (noche-amatista), fucsia (noche-fucsia) y verde (noche-verde).

Las escalas `zinc`, `purple`, `rose` y `emerald` de Tailwind están redefinidas en `app.css` sobre estos colores, así que una clase como `text-zinc-600` o `bg-rose-600` ya pertenece al mundo.

### Named Rules
**The Tinta de Tampón Rule.** El color solo aparece como tinta: en sellos, enlaces, el botón principal y el foco. Fondos de color de bloque no existen; los tintes tenues (purple-50 al pasar por una fila) son lo máximo.

**The Chartreuse Casi Nunca Rule.** Chartreuse no lleva texto sobre fondo claro salvo en su variante legible, y como tinta solo marca «hoy» en modo oscuro.

## Typography

**Display Font:** Atkinson Hyperlegible Next (con ui-sans-serif, system-ui)
**Body Font:** Atkinson Hyperlegible Next
**Label/Mono Font:** Atkinson Hyperlegible Mono (con ui-monospace), solo para hashes, fechas, números y sellos

**Character:** Una familia pensada para leerse bien, autoalojada, que da a la interfaz un tono claro y escolar; la Mono pone el golpe de tampón en fechas y hashes.

### Hierarchy
- **Display** (800, 3rem en escritorio / 2.25rem en móvil, 1.1): solo el titular de la portada pública.
- **Headline** (800, 1.875rem): el saludo del inicio y los h1 de página; la cabecera del repositorio usa 1.5rem con el proyecto en 600 y tinta suave.
- **Title** (800, 1.125rem): nombre de repositorio en su ficha y títulos de bloque.
- **Body** (400, 0.875rem, 1.25rem de interlínea): el tamaño de trabajo de toda la app; las frases-resumen suben a **Lead** (1rem) y la portada a 1.125rem. Negritas de 700 para cifras y nombres dentro de frases.
- **Label** (700, 11px, 0.05em, MAYÚSCULAS): cabeceras de columna («Cambio · quién», «Sellado»), términos de listas de datos y encabezados de sección cortos como «Te toca» (800, 14px).
- **Sello** (Mono 700, 11px, MAYÚSCULAS, cifras tabulares): fechas selladas («HOY 19:19», «3 OCT»).

### Named Rules
**The Peso Antes Que Tamaño Rule.** La jerarquía se hace con peso y mayúscula; no se añaden tamaños nuevos a la escala. Si algo necesita destacar, sube a 700/800 antes que a otro tamaño.

**The Cifra Tabular Rule.** Todo `.font-mono`, `code`, `kbd` y `time` usa cifras tabulares para que las columnas cuadren; los números (#12, e8b1195) se ven siempre y no cambian de ancho.

## Layout

Contenedor de 1280px de ancho máximo, centrado, con márgenes de 16/24/32px según el ancho y 32px de aire vertical en el contenido. Barra superior blanca y fija de 64px con regla inferior.

El inicio es una rejilla de dos columnas desde 1024px: contenido flexible a la izquierda y una columna lateral de 380px («Te toca») separadas 40px; por debajo se apila (saludo, «Te toca», fichas). Las fichas se apilan con 20px entre ellas y tienen 20px de relleno. Dentro de una ficha de repositorio, a partir de 768px los datos van a la izquierda y la columna rayada de 22rem a la derecha, separada por una regla vertical; en móvil la regla pasa a ser horizontal.

La unidad de ritmo es la fila de 36px: la cabecera de columna, cada fila de sello y el propio rayado de fondo miden lo mismo, así que el contenido cae siempre sobre la línea. Las listas de una ficha se dividen con reglas interiores en vez de huecos.

## Elevation & Depth

Sistema plano: la profundidad la dan las reglas de 1px y el contraste cartulina/ficha, no las sombras. Las sombras del tema están reducidas a reglas casi invisibles; la única elevación real es la de los menús desplegables y el enlace «Saltar al contenido». La barra de navegación elástica y el buscador conservan su cristal esmerilado heredado (desenfoque de 8px y sombra suave), que es propio de esos controles.

### Shadow Vocabulary
- **Regla de base** (`box-shadow: 0 1px 0 rgb(16 19 35 / 0.06)`): equivalente a shadow-sm; apenas un filo bajo controles.
- **Menú** (`box-shadow: 0 12px 28px -8px rgb(16 19 35 / 0.22)`): menús desplegables de usuario y de navegación móvil.

### Named Rules
**The Solo Flotan Los Menús Rule.** Las fichas, tarjetas, paneles y botones no llevan sombra. Lo único que se levanta del papel es lo que se abre por encima de él.

## Shapes

Esquinas pequeñas y uniformes: 4px en botones y campos, 6px en fichas, tarjetas y el sello grande de estado, 8px en menús. El sello de fecha tiene 3px y se gira entre -1.5° y 1.5° (el giro varía de una fila a otra, como sellado a mano). La forma lleva significado: el sello redondo (999px) es hecho o fusionado, el rectangular es pendiente y el tachado es cerrado. Los bordes de sello son de 1.5px en el color de su tinta (`currentColor`), sin relleno; los de ficha, de 1px.

## Components

### Buttons
- **Shape:** esquinas suaves (4px), texto en 700, sin sombra.
- **Primary:** fondo fucsia de acción con texto blanco; 6px × 12px en tamaño normal, 4px × 10px y 12px en pequeño, 10px × 16px en grande.
- **Hover / Focus:** transición de color de 150ms; al pulsar se hunde a escala 0.98; foco con anillo de 2px y 2px de separación en el color de la variante.
- **Secondary:** fondo de ficha, regla de 1px y tinta oscura; es el botón por defecto para acciones de navegación («Abrir el código», «Mis proyectos»).
- **Ghost:** solo texto en tinta media, fondo zinc-100 al pasar.
- **Amatista / Éxito / Peligro:** amatista sólido; verde sello sólido para fusionar; peligro como contorno fucsia que se rellena al pasar.

### Chips (sellos y etiquetas)
- **Style:** sin relleno, borde de 1.5px en la tinta del estado, MAYÚSCULAS en 700. El sello de fecha usa la Mono a 11px; la etiqueta (`x-badge`) usa la sans con tracking amplio.
- **State:** redondo (hecho/fusionado/aprobado), rectangular girado (pendiente), tachado (cerrado). Las insignias de estado de PR, issue y revisión eligen la forma a partir del estado.
- **Sello grande de estado:** casilla de 40px con borde de 2px e icono, cuadrada (pendiente, fucsia) o redonda (hecho, verde), usada en la columna «Te toca».

### Cards / Containers
- **Corner Style:** 6px.
- **Background:** ficha (blanco) en claro, ficha de noche en oscuro.
- **Shadow Strategy:** ninguna (ver Elevation & Depth).
- **Border:** regla de 1px; reglas interiores para cabeceras y filas.
- **Internal Padding:** 20px en fichas; 12px × 16px en cabeceras de tarjeta y filas de lista.

### Inputs / Fields
- **Style:** fondo de ficha, regla de 1px, esquinas de 4px, 8px × 12px, texto de 14px.
- **Focus:** el borde pasa a amatista con un halo amatista al 20% de 2px; el cursor de texto es fucsia.

### Navigation
- **Barra superior:** blanca, 64px, regla inferior; logo, barra de pestañas con indicador elástico (componente heredado, con resorte), buscador que se expande, botón fucsia «Nuevo», campana y menú de usuario.
- **Pestañas de sección:** texto de 14px en tinta media; la activa pasa a tinta en 500 con subrayado de 2px en fucsia de marca. La variante de filtro (píldoras) va en un marco de ficha con la activa en zinc-100.
- **Móvil:** menú desplegable bajo hamburguesa y barra inferior propia.

### Ficha con columna rayada (Signature Component)
La ficha de repositorio: datos a la izquierda (nombre, etiqueta Público/Privado, rama y último hash en Mono, dos botones) y a la derecha la columna rayada con cabecera «Cambio · quién / Sellado» y una fila de 36px por cambio, cada una con su sello de fecha. El sello más reciente de hoy se marca como nuevo. Al entrar, los sellos «caen» con la animación `sellar` (420ms, `cubic-bezier(0.16, 1, 0.3, 1)`, escalonados 70ms): bajan de escala 1.3 con un leve desenfoque y se asientan, sin tocar nunca la opacidad. Con movimiento reducido no se animan.

### Anclas del tutorial (restricción del sistema)
El tutorial de iniciación se engancha con atributos `data-tour` cuya lista estable está en `docs/ANCLAS_TUTORIAL.md`: `nav-home`, `nav-projects`, `nav-explore`, `nav-activity`, `nav-notifications`, `buscar`, `nuevo-proyecto`, `avisos`, `menu-usuario`, `resumen`, `ficha-repositorio`, `sellos`, `te-toca`, `pestana-codigo`, `pestana-issues`, `pestana-pulls`, `pestana-commits`, `pestana-ramas`, `selector-rama`, `subir-archivos`. Cualquier rediseño debe conservar cada ancla en el elemento equivalente, aunque cambien clases, textos o estructura.

## Do's and Don'ts

### Do:
- **Do** apoyar cada bloque en una ficha blanca con regla de 1px y esquinas de 6px sobre la cartulina.
- **Do** decir el estado con la forma del sello además del color: redondo hecho, rectangular pendiente, tachado cerrado.
- **Do** usar las escalas `zinc`, `purple`, `rose` y `emerald` redefinidas (o las variables `--color-stamp*`, `--color-rule*`) en vez de hex sueltos.
- **Do** alinear listas de cambios a la fila de 36px de la columna rayada.
- **Do** poner hashes, fechas y cifras en Atkinson Hyperlegible Mono con cifras tabulares.
- **Do** conservar todas las anclas `data-tour` de `docs/ANCLAS_TUTORIAL.md` en cualquier rediseño y añadir las nuevas con nombres en español y guiones.
- **Do** respetar `prefers-reduced-motion` en toda animación nueva, como hacen los sellos y la barra elástica.

### Don't:
- **Don't** construir el dashboard gris de tarjetas flotantes con métricas gigantes, ni neón sobre negro.
- **Don't** poner sombra a fichas, tarjetas o botones; solo flotan los menús y los controles animados heredados.
- **Don't** usar negro puro en modo oscuro; la noche es azul tinta.
- **Don't** usar chartreuse como texto sobre fondo claro ni como color de acción.
- **Don't** añadir tamaños de texto nuevos para crear jerarquía; usa peso y mayúscula.
- **Don't** usar una etiqueta en mayúsculas como antetítulo sobre un titular; las mayúsculas pequeñas son para cabeceras de columna, términos de datos y encabezados cortos de sección.
- **Don't** reutilizar las anclas `data-tour` para estilos ni para tests.
