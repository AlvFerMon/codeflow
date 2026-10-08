<x-guest-layout title="Crear cuenta">
    <h1 class="mb-1 text-xl font-semibold text-slate-900">Crear cuenta</h1>
    <p class="mb-6 text-sm text-slate-500">Regístrate para crear proyectos y colaborar en sus repositorios.</p>
    <form method="POST" action="{{ route('register.store') }}" class="space-y-4">
        @csrf
        <x-input label="Nombre" name="name" required autofocus autocomplete="name" maxlength="100" />
        <x-input label="Correo electrónico" name="email" type="email" required autocomplete="username" />
        <x-input label="Contraseña" name="password" type="password" required autocomplete="new-password" minlength="8" help="Mínimo 8 caracteres." />
        <x-input label="Repite la contraseña" name="password_confirmation" type="password" required autocomplete="new-password" minlength="8" />
        <x-button class="w-full">Crear cuenta</x-button>
    </form>
    <p class="mt-6 text-center text-sm text-slate-600">
        ¿Ya tienes cuenta? <a href="{{ route('login') }}" class="font-medium text-indigo-600 hover:underline">Inicia sesión</a>
    </p>
</x-guest-layout>
