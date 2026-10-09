<x-guest-layout title="Nueva contraseña">
    <h1 class="mb-1 text-xl font-semibold text-zinc-900 dark:text-zinc-100">Nueva contraseña</h1>
    <p class="mb-6 text-sm text-zinc-500 dark:text-zinc-400">Elige una contraseña nueva para tu cuenta.</p>
    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <x-input label="Correo electrónico" name="email" type="email" :value="$email" required autocomplete="username" />
        <x-input label="Contraseña nueva" name="password" type="password" required autofocus autocomplete="new-password" minlength="8" help="Mínimo 8 caracteres." />
        <x-input label="Repite la contraseña" name="password_confirmation" type="password" required autocomplete="new-password" minlength="8" />
        <x-button variant="purple" size="lg" class="w-full">Guardar contraseña</x-button>
    </form>
    <p class="mt-6 text-center text-sm text-zinc-600 dark:text-zinc-400">
        <a href="{{ route('login') }}" class="font-medium text-purple-600 dark:text-purple-400 hover:underline">Volver a iniciar sesión</a>
    </p>
</x-guest-layout>
