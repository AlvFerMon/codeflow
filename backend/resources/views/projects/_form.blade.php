{{-- Campos comunes de crear/editar proyecto. Variable opcional: $project --}}
<x-input label="Nombre" name="name" :value="$project->name ?? null" required maxlength="100" autofocus />
<x-textarea label="Descripción" name="description" :value="$project->description ?? null" rows="6" maxlength="2000" placeholder="¿De qué trata el proyecto? Admite Markdown." />
<p class="-mt-2 text-xs text-slate-500">Puedes usar Markdown: **negrita**, listas, enlaces, `código`…</p>
