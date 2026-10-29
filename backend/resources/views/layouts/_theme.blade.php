{{-- Aplica el tema antes de pintar la página (evita el parpadeo): el de la cuenta, el guardado en el navegador o el del sistema. --}}
<script>
    (function () {
        var theme = 'system';
        var saved = @json(\App\Support\Preferences::savedTheme(auth()->user()));
        try {
            theme = saved || localStorage.getItem('codeflow-theme') || 'system';
            if (saved) localStorage.setItem('codeflow-theme', saved);
        } catch (e) { theme = saved || 'system'; }
        var dark = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
        document.documentElement.classList.toggle('dark', dark);
        document.documentElement.dataset.theme = theme;
    })();
</script>
