<x-guest-layout title="Verifica tu email">
    <div class="text-center">
        <span class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-[#9448BC]/15 text-[#9448BC] dark:text-[#C687EB]">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>
        </span>
        <h1 class="mt-4 text-xl font-semibold text-zinc-900 dark:text-white">Esperando la verificación de tu email</h1>
        <p class="mt-2 text-sm leading-6 text-zinc-600 dark:text-zinc-400">
            Te hemos enviado un código de 6 dígitos a
            <span class="font-semibold text-zinc-900 dark:text-white">{{ $user->email }}</span>.
            Escríbelo aquí para activar tu cuenta.
        </p>
    </div>

    <form method="POST" action="{{ route('verification.verify') }}" class="mt-6 space-y-4">
        @csrf
        <div class="space-y-3">
            <label for="code" class="block text-center text-sm font-medium text-zinc-700 dark:text-zinc-300">Código de verificación</label>
            <x-one-time-code name="code" :length="6" :value="old('code', '')" :error="$errors->has('code')" autofocus />
            @error('code')
                <p class="text-center text-xs font-medium text-rose-600 dark:text-rose-400" role="alert">{{ $message }}</p>
            @enderror
        </div>
        <x-button variant="purple" size="lg" class="w-full">Verificar email</x-button>
    </form>

    <div class="mt-6 rounded-lg border border-zinc-200 bg-zinc-50 p-4 text-xs leading-5 text-zinc-600 dark:border-zinc-800 dark:bg-zinc-800/40 dark:text-zinc-400">
        <p class="font-medium text-zinc-700 dark:text-zinc-300">¿No te llega el código?</p>
        <ul class="mt-1 list-inside list-disc space-y-0.5">
            <li>Mira en la carpeta de spam o de promociones.</li>
            <li>El código caduca en {{ \App\Services\EmailVerificationService::MINUTES }} minutos y admite {{ \App\Services\EmailVerificationService::MAX_ATTEMPTS }} intentos.</li>
        </ul>
        <form method="POST" action="{{ route('verification.send') }}" class="mt-3">
            @csrf
            <x-button variant="secondary" size="sm">Reenviar código</x-button>
        </form>
    </div>

    <details class="mt-4 text-xs" @if ($errors->has('email')) open @endif>
        <summary class="cursor-pointer font-medium text-purple-600 hover:underline dark:text-purple-400">¿Has escrito mal tu email?</summary>
        <form method="POST" action="{{ route('verification.email') }}" class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-start">
            @csrf
            @method('PUT')
            <div class="flex-1"><x-input name="email" type="email" :value="$user->email" required aria-label="Email correcto" /></div>
            <x-button variant="secondary">Cambiar y reenviar</x-button>
        </form>
    </details>

    <div class="mt-6 flex items-center justify-between border-t border-zinc-100 pt-4 text-xs text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
        <span>Sesión iniciada como {{ $user->name }}</span>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="hover:text-rose-600 hover:underline">Cerrar sesión</button>
        </form>
    </div>
</x-guest-layout>
