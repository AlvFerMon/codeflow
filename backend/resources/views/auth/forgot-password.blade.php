<x-guest-layout title="Recuperar contraseña">
    <h1 class="mb-1 text-xl font-semibold text-slate-900">Recuperar contraseña</h1>
    <p class="mb-6 text-sm text-slate-500">Escribe el correo de tu cuenta y te enviaremos un enlace para elegir una contraseña nueva.</p>
    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf
        <x-input label="Correo electrónico" name="email" type="email" required autofocus autocomplete="username" />
        <x-button class="w-full">Enviar enlace</x-button>
    </form>
    <p class="mt-6 text-center text-sm text-slate-600">
        <a href="{{ route('login') }}" class="font-medium text-indigo-600 hover:underline">Volver a iniciar sesión</a>
    </p>
</x-guest-layout>
