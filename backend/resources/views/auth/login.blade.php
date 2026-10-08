<x-guest-layout title="Iniciar sesión">
    <h1 class="mb-6 text-xl font-semibold text-slate-900">Iniciar sesión</h1>
    <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
        @csrf
        <x-input label="Correo electrónico" name="email" type="email" required autofocus autocomplete="username" />
        <x-input label="Contraseña" name="password" type="password" required autocomplete="current-password" />
        <div class="flex items-center justify-between text-sm">
            <label class="flex items-center gap-2 text-slate-600">
                <input type="checkbox" name="remember" class="rounded border-slate-300 text-indigo-600"> Recordarme
            </label>
            <a href="{{ route('password.request') }}" class="text-indigo-600 hover:underline">¿Has olvidado la contraseña?</a>
        </div>
        <x-button class="w-full">Entrar</x-button>
    </form>
    <p class="mt-6 text-center text-sm text-slate-600">
        ¿No tienes cuenta? <a href="{{ route('register') }}" class="font-medium text-indigo-600 hover:underline">Regístrate</a>
    </p>
    <p class="mt-2 text-center text-sm">
        <a href="{{ route('explore') }}" class="text-slate-500 hover:underline">Ver repositorios públicos sin iniciar sesión</a>
    </p>
</x-guest-layout>
