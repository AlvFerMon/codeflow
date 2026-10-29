<?php

/*
|--------------------------------------------------------------------------
| Tutorial de bienvenida
|--------------------------------------------------------------------------
|
| 'tour': visita guiada que se muestra al entrar por primera vez (con el correo verificado).
|   Cada paso resalta el elemento con el atributo data-tour="<anchor>" (lista en docs/ANCLAS_TUTORIAL.md); si no hay anchor o el
|   elemento no está visible (p. ej. en el móvil), el paso se muestra centrado.
|
| 'checklist': lista «Primeros pasos». Cada tarea se marca sola cuando el usuario hace esa
|   acción de verdad (se detecta en el registro de actividad o en los tokens de sus apps).
|   Las acciones del proyecto de práctica no cuentan. 'optional' => no hace falta para completarla.
|
*/

return [

    'tour' => [
        [
            'id' => 'welcome',
            'title' => 'Te damos la bienvenida a CodeFlow',
            'text' => 'Aquí guardas tu código y trabajas en equipo. En un minuto te enseñamos lo básico. Puedes saltártelo y volver a verlo cuando quieras desde tu menú de usuario.',
            'anchor' => null,
        ],
        [
            'id' => 'concepts',
            'title' => 'Cuatro palabras que vas a ver mucho',
            'text' => "Proyecto: tu espacio de trabajo y tu equipo.\nRepositorio: una carpeta con el código y todo su historial.\nCommit: una foto de tus cambios con un mensaje que los explica.\nRama: una copia para probar cosas sin tocar la versión principal.",
            'anchor' => null,
        ],
        [
            'id' => 'todo',
            'title' => 'Lo que te toca',
            'text' => 'Aquí aparece lo que espera algo de ti: revisiones que te piden, issues asignadas y lo último que se ha fusionado.',
            'anchor' => 'te-toca',
        ],
        [
            'id' => 'projects',
            'title' => 'Tus proyectos',
            'text' => 'Desde aquí entras a tus proyectos y sus repositorios.',
            'anchor' => 'nav-projects',
        ],
        [
            'id' => 'new',
            'title' => 'Crea tu primer proyecto',
            'text' => 'Con este botón creas un proyecto. Dentro podrás añadir repositorios e invitar a tu equipo.',
            'anchor' => 'nuevo-proyecto',
        ],
        [
            'id' => 'notifications',
            'title' => 'Avisos',
            'text' => 'Te avisamos cuando alguien sube cambios, te asigna una tarea o revisa tu trabajo.',
            'anchor' => 'avisos',
        ],
        [
            'id' => 'downloads',
            'title' => 'Apps para el ordenador y el móvil',
            'text' => 'Con la app de escritorio subes tus cambios con un clic, sin usar la terminal.',
            'anchor' => 'nav-downloads',
        ],
        [
            'id' => 'menu',
            'title' => 'Tu menú',
            'text' => 'Aquí están tu perfil, el modo claro u oscuro y «Ver el tutorial otra vez».',
            'anchor' => 'menu-usuario',
        ],
        [
            'id' => 'checklist',
            'title' => 'Primeros pasos',
            'text' => 'Esta lista te guía por lo más importante y se marca sola según avanzas. Si quieres practicar sin miedo, crea el proyecto de ejemplo.',
            'anchor' => 'primeros-pasos',
        ],
    ],

    'checklist' => [
        ['id' => 'create_project', 'title' => 'Crea un proyecto', 'text' => 'Es tu espacio de trabajo.', 'route' => 'projects.create', 'activity' => ['project.created']],
        ['id' => 'create_repository', 'title' => 'Crea un repositorio', 'text' => 'Dentro de un proyecto, para guardar código.', 'route' => 'projects.index', 'activity' => ['repository.created']],
        ['id' => 'first_commit', 'title' => 'Sube tu primer cambio', 'text' => 'Crea o edita un archivo y guárdalo con un mensaje (commit).', 'route' => 'projects.index', 'activity' => ['commit.created']],
        ['id' => 'create_branch', 'title' => 'Crea una rama', 'text' => 'Para probar cambios sin tocar la rama principal.', 'route' => 'projects.index', 'activity' => ['branch.created']],
        ['id' => 'open_issue', 'title' => 'Abre una issue', 'text' => 'Apunta una tarea o un error para el equipo.', 'route' => 'projects.index', 'activity' => ['issue.created']],
        ['id' => 'open_pull_request', 'title' => 'Abre un pull request', 'text' => 'Propón llevar los cambios de tu rama a la principal.', 'route' => 'projects.index', 'activity' => ['pull_request.opened']],
        ['id' => 'invite_member', 'title' => 'Invita a alguien', 'text' => 'Añade a un compañero a tu proyecto.', 'route' => 'projects.index', 'activity' => ['member.added', 'member.invited']],
        ['id' => 'connect_app', 'title' => 'Instala la app de escritorio o la del móvil', 'text' => 'Opcional: trabaja fuera del navegador.', 'route' => 'downloads', 'tokens' => ['codeflow-cli%', 'codeflow-desktop%', 'Android %'], 'optional' => true],
    ],

    // Días que la lista queda oculta al pulsar «Ocultar» (vuelve mientras no esté completa)
    'checklist_snooze_days' => 7,

];
