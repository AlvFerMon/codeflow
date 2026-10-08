# CodeFlow — backend (Laravel 13, PHP 8.3)

- Monorepo: este directorio es el backend. Docker en la raíz (`docker compose up -d --build`).
- Documentación de arquitectura: `../docs/ARQUITECTURA.md`.
- Control de versiones propio en MySQL (`app/Services/VcsService.php`, `app/Vcs/**`), con cifrado por repositorio:
  contenido de archivos y mensajes de commit cifrados (`app/Casts/RepositoryEncrypted.php`). Nunca leer `blobs.content`
  ni `commits.message` sin pasar por los modelos.
- Lógica de negocio en `app/Services/*`; autorización en `app/Policies/*` (roles owner/collaborator/observer + admin global).
  La API (`routes/api.php`) y la web Blade (`routes/web/*.php`) usan los mismos servicios y policies.
- Textos de interfaz en español; identificadores en inglés. No añadir dependencias sin necesidad.
- Tests: `php artisan test` (SQLite en memoria). Deben pasar todos antes de dar un cambio por terminado.
