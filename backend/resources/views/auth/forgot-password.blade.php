<x-guest-layout title="Recuperar contraseña">
    <h1 class="mb-1 text-xl font-semibold text-zinc-900 dark:text-zinc-100">Recuperar contraseña</h1>
    <p class="mb-6 text-sm text-zinc-500 dark:text-zinc-400">Escribe el correo de tu cuenta y te enviaremos un enlace para elegir una contraseña nueva.</p>
    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf
        <x-input label="Correo electrónico" name="email" type="email" required autofocus autocomplete="username" />
        <x-button variant="purple" size="lg" class="w-full">Enviar enlace</x-button>
    </form>
    <p class="mt-6 text-center text-sm text-zinc-600 dark:text-zinc-400">
        <a href="{{ route('login') }}" class="font-medium text-purple-600 dark:text-purple-400 hover:underline">Volver a iniciar sesión</a>
    </p>
</x-guest-layout>
