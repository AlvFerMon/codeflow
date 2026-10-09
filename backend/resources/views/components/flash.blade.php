@if (session('status'))
    <div role="status" data-dismissible class="mb-4 flex items-start gap-2 rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-800 dark:text-emerald-300">
        <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
        <span>{{ session('status') }}</span>
        <button type="button" data-dismiss class="ml-auto -mr-1 rounded p-0.5 opacity-60 hover:opacity-100 focus-visible:opacity-100" aria-label="Cerrar mensaje"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg></button>
    </div>
@endif
@if (session('error'))
    <div role="alert" data-dismissible class="mb-4 flex items-start gap-2 rounded-xl border border-rose-500/20 bg-rose-500/10 px-4 py-3 text-sm text-rose-800 dark:text-rose-300">
        <span>{{ session('error') }}</span>
        <button type="button" data-dismiss class="ml-auto -mr-1 rounded p-0.5 opacity-60 hover:opacity-100 focus-visible:opacity-100" aria-label="Cerrar mensaje"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg></button>
    </div>
@endif
@if ($errors->any())
    <div role="alert" class="mb-4 rounded-xl border border-rose-500/20 bg-rose-500/10 px-4 py-3 text-sm text-rose-800 dark:text-rose-300">
        <ul class="list-inside list-disc space-y-0.5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
