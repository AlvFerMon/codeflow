{{-- Diálogo de confirmación compartido (resources/js/ui.js). Se usa con <form data-confirm="…">. --}}
<dialog id="confirm-dialog" aria-labelledby="confirm-dialog-title" aria-describedby="confirm-dialog-message"
    class="m-auto w-[calc(100%-2rem)] max-w-md rounded-xl border border-zinc-200 bg-white p-0 text-zinc-900 shadow-2xl backdrop:bg-zinc-950/50 backdrop:backdrop-blur-sm dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-100">
    <form method="dialog" class="p-6">
        <div class="flex gap-4">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-rose-500/10 text-rose-600 dark:text-rose-400" aria-hidden="true">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
            </span>
            <div class="min-w-0">
                <h2 id="confirm-dialog-title" data-confirm-title class="text-base font-semibold">¿Seguro?</h2>
                <p id="confirm-dialog-message" data-confirm-message class="mt-1 text-sm leading-6 text-zinc-600 dark:text-zinc-400"></p>
            </div>
        </div>
        <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <button value="cancel" data-confirm-cancel class="rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm font-medium text-zinc-800 hover:bg-zinc-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700">Cancelar</button>
            <button value="ok" data-confirm-accept class="rounded-lg bg-rose-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-rose-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-zinc-900">Confirmar</button>
        </div>
    </form>
</dialog>
