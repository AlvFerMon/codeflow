{{-- Aplica el tema antes de pintar la página (evita el parpadeo): preferencia guardada o la del sistema. --}}
<script>
    (function () {
        var theme = 'system';
        try { theme = localStorage.getItem('codeflow-theme') || 'system'; } catch (e) {}
        var dark = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
        document.documentElement.classList.toggle('dark', dark);
        document.documentElement.dataset.theme = theme;
    })();
</script>
