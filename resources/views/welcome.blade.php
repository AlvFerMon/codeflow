@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    <!-- Header / Banner de Bienvenida -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-6 border-b border-zinc-200/80 dark:border-zinc-800">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold tracking-tight text-zinc-950 dark:text-white">Flujo de Trabajo</h1>
                <x-badge variant="purple" size="xs">Workspace Activo</x-badge>
            </div>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                Monitorea repositorios, controla versiones y colabora en ramas en tiempo real con la suite CodeFlow.
            </p>
        </div>

        <!-- Acciones Rápidas -->
        <div class="flex flex-wrap items-center gap-2.5">
            <x-button variant="secondary" size="sm">
                <svg class="w-3.5 h-3.5 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <span>Nueva Rama</span>
            </x-button>

            <x-button variant="secondary" size="sm">
                <svg class="w-3.5 h-3.5 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 7.5h-.75A2.25 2.25 0 0 0 4.5 9.75v7.5a2.25 2.25 0 0 0 2.25 2.25h7.5a2.25 2.25 0 0 0 2.25-2.25v-.75m-6-3-3-3m0 0 3-3m-3 3H15m2.25-3h.75A2.25 2.25 0 0 1 20.25 6v7.5a2.25 2.25 0 0 1-2.25 2.25h-.75" />
                </svg>
                <span>Nuevo PR</span>
            </x-button>

            <x-button variant="primary" size="sm">
                <svg class="w-3.5 h-3.5 text-white dark:text-zinc-950" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>Nuevo Repositorio</span>
            </x-button>
        </div>
    </div>

    <!-- Métricas Rápidas (KPIs) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Repositorios -->
        <x-metric-card title="Repositorios" value="12" change="+2 este mes" accent="blue">
            <x-slot:icon>
                <svg class="w-5 h-5 text-sky-600 dark:text-sky-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 0 0-1.883 2.542l.857 6a2.25 2.25 0 0 0 2.227 1.932H19.05a2.25 2.25 0 0 0 2.227-1.932l.857-6a2.25 2.25 0 0 0-1.883-2.542m-16.5 0V6A2.25 2.25 0 0 1 6 3.75h3.879a1.5 1.5 0 0 1 1.06.44l2.122 2.12a1.5 1.5 0 0 0 1.06.44H18A2.25 2.25 0 0 1 20.25 9v.776" />
                </svg>
            </x-slot:icon>
            <x-slot:footer>
                <span class="inline-flex items-center gap-1 text-emerald-600 font-medium">● 8 públicos</span> · 4 privados
            </x-slot:footer>
        </x-metric-card>

        <!-- Pull Requests -->
        <x-metric-card title="Pull Requests" value="5" change="2 pendientes" accent="green">
            <x-slot:icon>
                <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 7.5h-.75A2.25 2.25 0 0 0 4.5 9.75v7.5a2.25 2.25 0 0 0 2.25 2.25h7.5a2.25 2.25 0 0 0 2.25-2.25v-.75m-6-3-3-3m0 0 3-3m-3 3H15m2.25-3h.75A2.25 2.25 0 0 1 20.25 6v7.5a2.25 2.25 0 0 1-2.25 2.25h-.75" />
                </svg>
            </x-slot:icon>
            <x-slot:footer>
                <span class="text-zinc-500">3 listos para merge · 0 bloqueados</span>
            </x-slot:footer>
        </x-metric-card>

        <!-- Issues -->
        <x-metric-card title="Issues Abiertas" value="8" change="3 de alta prioridad" accent="amber">
            <x-slot:icon>
                <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                </svg>
            </x-slot:icon>
            <x-slot:footer>
                <span class="text-zinc-500">5 resueltas en los últimos 7 días</span>
            </x-slot:footer>
        </x-metric-card>

        <!-- Commits -->
        <x-metric-card title="Commits en Red" value="142" change="Hoy en 6 proyectos" accent="purple">
            <x-slot:icon>
                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.129-1.125V14.25M8.25 5.25a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375C2.754 5.25 2.25 5.754 2.25 6.375V8.25m17.25-3a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129.504 1.129 1.125V8.25" />
                </svg>
            </x-slot:icon>
            <x-slot:footer>
                <span class="font-mono text-purple-600 dark:text-purple-400">VcsService v1.0 engine</span>
            </x-slot:footer>
        </x-metric-card>

    </div>

    <!-- Contenido Principal Dividido en 2 Columnas -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">

        <!-- Columna Izquierda (2/3): Repositorios y Pull Requests -->
        <div class="lg:col-span-2 space-y-8">
            
            <!-- Bloque de Repositorios Recientes -->
            <section id="repositorios" class="rounded-xl border border-zinc-200/80 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-5 border-b border-zinc-100 dark:border-zinc-800">
                    <div>
                        <h2 class="text-base font-semibold text-zinc-900 dark:text-white">Repositorios Recientes</h2>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Tus árboles de código versionados y ramas activas</p>
                    </div>

                    <div class="flex items-center gap-1.5 self-start sm:self-auto">
                        <button class="px-2.5 py-1 text-xs font-semibold rounded-md bg-zinc-100 text-zinc-900 dark:bg-zinc-800 dark:text-zinc-100">Todos (12)</button>
                        <button class="px-2.5 py-1 text-xs font-medium rounded-md text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100 transition-colors">Favoritos (4)</button>
                    </div>
                </div>

                <!-- Lista de Repositorios -->
                <div class="divide-y divide-zinc-100 dark:divide-zinc-800 mt-2">
                    
                    <!-- Repositorio 1 -->
                    <div class="py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 group hover:bg-zinc-50/50 dark:hover:bg-zinc-800/20 px-2 -mx-2 rounded-lg transition-colors">
                        <div class="space-y-1.5">
                            <div class="flex items-center gap-2">
                                <a href="#repo/core-engine" class="text-sm font-semibold text-zinc-900 hover:text-purple-600 dark:text-white dark:hover:text-purple-400 transition-colors">
                                    codeflow / core-engine
                                </a>
                                <x-badge variant="zinc" size="xs">Privado</x-badge>
                                <x-badge variant="purple" size="xs">
                                    <svg class="w-3 h-3 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                                    </svg>
                                    main
                                </x-badge>
                            </div>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                Motor de control de versiones y cálculo de diffs para almacenamiento de blobs y árboles.
                            </p>
                            <div class="flex items-center gap-4 text-xs text-zinc-400 pt-1">
                                <span class="flex items-center gap-1 text-zinc-600 dark:text-zinc-300">
                                    <span class="h-2 w-2 rounded-full bg-indigo-500"></span> PHP 8.4
                                </span>
                                <span>Último commit: <span class="font-mono text-zinc-700 dark:text-zinc-300">9b2f1e</span> hace 12 min</span>
                                <span class="flex items-center gap-1">★ 24</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <x-button variant="secondary" size="sm">Explorar</x-button>
                        </div>
                    </div>

                    <!-- Repositorio 2 -->
                    <div class="py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 group hover:bg-zinc-50/50 dark:hover:bg-zinc-800/20 px-2 -mx-2 rounded-lg transition-colors">
                        <div class="space-y-1.5">
                            <div class="flex items-center gap-2">
                                <a href="#repo/web-client" class="text-sm font-semibold text-zinc-900 hover:text-purple-600 dark:text-white dark:hover:text-purple-400 transition-colors">
                                    codeflow / web-client
                                </a>
                                <x-badge variant="success" size="xs">Público</x-badge>
                                <x-badge variant="purple" size="xs">
                                    <svg class="w-3 h-3 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                                    </svg>
                                    main
                                </x-badge>
                            </div>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                Interfaz web moderna implementada con Laravel Blade, Tailwind CSS y Vite.
                            </p>
                            <div class="flex items-center gap-4 text-xs text-zinc-400 pt-1">
                                <span class="flex items-center gap-1 text-zinc-600 dark:text-zinc-300">
                                    <span class="h-2 w-2 rounded-full bg-cyan-500"></span> Tailwind / JS
                                </span>
                                <span>Último commit: <span class="font-mono text-zinc-700 dark:text-zinc-300">4a78c0</span> hace 45 min</span>
                                <span class="flex items-center gap-1">★ 18</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <x-button variant="secondary" size="sm">Explorar</x-button>
                        </div>
                    </div>

                    <!-- Repositorio 3 -->
                    <div class="py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 group hover:bg-zinc-50/50 dark:hover:bg-zinc-800/20 px-2 -mx-2 rounded-lg transition-colors">
                        <div class="space-y-1.5">
                            <div class="flex items-center gap-2">
                                <a href="#repo/git-bridge" class="text-sm font-semibold text-zinc-900 hover:text-purple-600 dark:text-white dark:hover:text-purple-400 transition-colors">
                                    codeflow / git-bridge
                                </a>
                                <x-badge variant="zinc" size="xs">Privado</x-badge>
                                <x-badge variant="purple" size="xs">
                                    <svg class="w-3 h-3 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                                    </svg>
                                    develop
                                </x-badge>
                            </div>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                Daemon de sincronización bidireccional entre repositorios remotos y almacenamiento local.
                            </p>
                            <div class="flex items-center gap-4 text-xs text-zinc-400 pt-1">
                                <span class="flex items-center gap-1 text-zinc-600 dark:text-zinc-300">
                                    <span class="h-2 w-2 rounded-full bg-blue-500"></span> Go / Libgit2
                                </span>
                                <span>Último commit: <span class="font-mono text-zinc-700 dark:text-zinc-300">e10fd4</span> hace 3 horas</span>
                                <span class="flex items-center gap-1">★ 9</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <x-button variant="secondary" size="sm">Explorar</x-button>
                        </div>
                    </div>

                </div>
            </section>

            <!-- Bloque de Pull Requests que requieren atención -->
            <section id="pull-requests" class="rounded-xl border border-zinc-200/80 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex items-center justify-between pb-4 border-b border-zinc-100 dark:border-zinc-800">
                    <div>
                        <h2 class="text-base font-semibold text-zinc-900 dark:text-white">Pull Requests Pendientes</h2>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Propuestas de cambio listas para revisión o fusión</p>
                    </div>
                    <a href="#prs" class="text-xs font-semibold text-purple-600 hover:text-purple-700 dark:text-purple-400">Ver todos (5) →</a>
                </div>

                <div class="divide-y divide-zinc-100 dark:divide-zinc-800 mt-2">
                    
                    <!-- PR Item 1 -->
                    <div class="py-3.5 flex items-start justify-between gap-4">
                        <div class="flex items-start gap-3">
                            <div class="mt-0.5 rounded-full p-1 bg-emerald-500/10 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 7.5h-.75A2.25 2.25 0 0 0 4.5 9.75v7.5a2.25 2.25 0 0 0 2.25 2.25h7.5a2.25 2.25 0 0 0 2.25-2.25v-.75m-6-3-3-3m0 0 3-3m-3 3H15m2.25-3h.75A2.25 2.25 0 0 1 20.25 6v7.5a2.25 2.25 0 0 1-2.25 2.25h-.75" />
                                </svg>
                            </div>
                            <div class="space-y-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-xs font-bold text-zinc-400">#142</span>
                                    <a href="#pr/142" class="text-xs font-semibold text-zinc-900 hover:text-purple-600 dark:text-white dark:hover:text-purple-400">
                                        feat(vcs): optimizar recorrido paralelo del árbol de commits
                                    </a>
                                    <x-badge variant="warning" size="xs">Revisión requerida</x-badge>
                                </div>
                                <div class="text-[11px] text-zinc-500 dark:text-zinc-400 flex items-center gap-2">
                                    <span>Rama: <span class="font-mono text-purple-600 dark:text-purple-400">feature/parallel-tree</span> hacia <span class="font-mono">main</span></span>
                                    <span>•</span>
                                    <span>Por Elena R. hace 28 min</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <span class="text-[11px] font-mono text-emerald-600 dark:text-emerald-400">+342</span>
                            <span class="text-[11px] font-mono text-rose-500">-89</span>
                        </div>
                    </div>

                    <!-- PR Item 2 -->
                    <div class="py-3.5 flex items-start justify-between gap-4">
                        <div class="flex items-start gap-3">
                            <div class="mt-0.5 rounded-full p-1 bg-purple-500/10 text-purple-600 dark:bg-purple-500/20 dark:text-purple-400">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                            </div>
                            <div class="space-y-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-xs font-bold text-zinc-400">#141</span>
                                    <a href="#pr/141" class="text-xs font-semibold text-zinc-900 hover:text-purple-600 dark:text-white dark:hover:text-purple-400">
                                        fix(auth): invalidar tokens y cookies en logout de sesión
                                    </a>
                                    <x-badge variant="success" size="xs">Aprobado</x-badge>
                                </div>
                                <div class="text-[11px] text-zinc-500 dark:text-zinc-400 flex items-center gap-2">
                                    <span>Rama: <span class="font-mono text-purple-600 dark:text-purple-400">fix/session-cleanup</span> hacia <span class="font-mono">main</span></span>
                                    <span>•</span>
                                    <span>Por Carlos M. hace 2 horas</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <span class="text-[11px] font-mono text-emerald-600 dark:text-emerald-400">+18</span>
                            <span class="text-[11px] font-mono text-rose-500">-4</span>
                        </div>
                    </div>

                </div>
            </section>

        </div>

        <!-- Columna Derecha (1/3): Feed de Actividad y Estado -->
        <div class="space-y-6">

            <!-- Actividad Reciente (Live Feed) -->
            <section id="actividad" class="rounded-xl border border-zinc-200/80 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex items-center justify-between pb-3 border-b border-zinc-100 dark:border-zinc-800">
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">Feed de Actividad</h2>
                    <span class="inline-flex items-center gap-1 text-[11px] text-emerald-600 font-medium">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span> En vivo
                    </span>
                </div>

                <div class="relative pl-4 mt-4 space-y-4 before:absolute before:left-1.5 before:top-2 before:bottom-2 before:w-0.5 before:bg-zinc-200 dark:before:bg-zinc-800">
                    
                    <!-- Evento 1 -->
                    <div class="relative flex items-start gap-3 text-xs">
                        <div class="absolute -left-[19px] top-1 h-2.5 w-2.5 rounded-full bg-purple-600 ring-4 ring-white dark:ring-zinc-900"></div>
                        <div>
                            <p class="text-zinc-800 dark:text-zinc-200 font-medium">
                                <span class="font-semibold text-zinc-950 dark:text-white">Alex R.</span> hizo push de 2 commits en <span class="font-mono text-purple-600 dark:text-purple-400">core-engine@main</span>
                            </p>
                            <span class="text-[11px] text-zinc-400">hace 12 minutos</span>
                        </div>
                    </div>

                    <!-- Evento 2 -->
                    <div class="relative flex items-start gap-3 text-xs">
                        <div class="absolute -left-[19px] top-1 h-2.5 w-2.5 rounded-full bg-emerald-500 ring-4 ring-white dark:ring-zinc-900"></div>
                        <div>
                            <p class="text-zinc-800 dark:text-zinc-200 font-medium">
                                <span class="font-semibold text-zinc-950 dark:text-white">Elena R.</span> abrió el <a href="#pr/142" class="underline font-semibold">PR #142</a>
                            </p>
                            <span class="text-[11px] text-zinc-400">hace 28 minutos</span>
                        </div>
                    </div>

                    <!-- Evento 3 -->
                    <div class="relative flex items-start gap-3 text-xs">
                        <div class="absolute -left-[19px] top-1 h-2.5 w-2.5 rounded-full bg-amber-500 ring-4 ring-white dark:ring-zinc-900"></div>
                        <div>
                            <p class="text-zinc-800 dark:text-zinc-200 font-medium">
                                <span class="font-semibold text-zinc-950 dark:text-white">Carlos M.</span> cerró la Issue <span class="font-semibold">#34</span> (Memory leak)
                            </p>
                            <span class="text-[11px] text-zinc-400">hace 2 horas</span>
                        </div>
                    </div>

                    <!-- Evento 4 -->
                    <div class="relative flex items-start gap-3 text-xs">
                        <div class="absolute -left-[19px] top-1 h-2.5 w-2.5 rounded-full bg-sky-500 ring-4 ring-white dark:ring-zinc-900"></div>
                        <div>
                            <p class="text-zinc-800 dark:text-zinc-200 font-medium">
                                <span class="font-semibold text-zinc-950 dark:text-white">David K.</span> creó la rama <span class="font-mono text-zinc-700 dark:text-zinc-300">fix/memory-leak</span>
                            </p>
                            <span class="text-[11px] text-zinc-400">hace 3 horas</span>
                        </div>
                    </div>

                </div>
            </section>

            <!-- Card de Clonación Rápida por Terminal -->
            <section class="rounded-xl border border-zinc-200/80 bg-zinc-900 p-5 text-white shadow-xs dark:border-zinc-800">
                <div class="flex items-center justify-between pb-2">
                    <span class="text-xs font-semibold text-zinc-300">Clonar repositorio vía CLI</span>
                    <span class="text-[10px] uppercase font-mono tracking-wider text-purple-400">HTTPS / SSH</span>
                </div>
                <div class="mt-2 flex items-center justify-between rounded-lg bg-zinc-950/80 p-2.5 font-mono text-[11px] text-zinc-300 border border-zinc-800">
                    <span class="truncate">git clone https://github.com/AlvFerMon/codeflow.git</span>
                    <button type="button" class="ml-2 rounded px-2 py-1 text-[10px] bg-zinc-800 hover:bg-zinc-700 text-zinc-200 transition-colors" title="Copiar comando">
                        Copiar
                    </button>
                </div>
                <p class="text-[11px] text-zinc-400 mt-2">
                    Listo para sincronizar con Laravel Boost y VcsService.
                </p>
            </section>

        </div>

    </div>

</div>
@endsection
