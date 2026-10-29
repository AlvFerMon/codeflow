<x-guest-layout title="Iniciar sesión">
    <h1 class="mb-6 text-xl font-semibold text-zinc-900 dark:text-zinc-100">Iniciar sesión</h1>
    <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
        @csrf
        <x-input label="Correo electrónico" name="email" type="email" :value="is_string(request('email')) ? request('email') : null" required autofocus autocomplete="username" />
        <x-input label="Contraseña" name="password" type="password" required autocomplete="current-password" />
        <div class="flex items-center justify-between text-sm">
            <label class="flex items-center gap-2 text-zinc-600 dark:text-zinc-400">
                <input type="checkbox" name="remember" class="rounded"> Recordarme
            </label>
            <a href="{{ route('password.request') }}" class="text-purple-600 dark:text-purple-400 hover:underline">¿Has olvidado la contraseña?</a>
        </div>
        <x-button variant="purple" size="lg" class="w-full">Entrar</x-button>
    </form>
    <p class="mt-6 text-center text-sm text-zinc-600 dark:text-zinc-400">
        ¿No tienes cuenta? <a href="{{ route('register') }}" class="font-medium text-purple-600 dark:text-purple-400 hover:underline">Regístrate</a>
    </p>
    {{-- En el móvil hay que iniciar sesión: no se ofrece explorar repositorios públicos --}}
    @unless (\App\Http\Controllers\Web\Mobile\MobileController::isPhone(request()))
    <p class="mt-2 text-center text-sm">
        <a href="{{ route('explore') }}" class="text-zinc-500 dark:text-zinc-400 hover:underline">Ver repositorios públicos sin iniciar sesión</a>
    </p>
    @endunless
</x-guest-layout>
