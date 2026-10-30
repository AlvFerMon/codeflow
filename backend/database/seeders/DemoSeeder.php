<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use App\Services\IssueService;
use App\Services\MembershipService;
use App\Services\ProjectService;
use App\Services\PullRequestService;
use App\Services\RepositoryService;
use App\Support\Roles;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Usuarios de prueba y un proyecto de ejemplo para ver CodeFlow funcionando.
 *
 * Contraseñas: variables SEED_ADMIN_PASSWORD y SEED_DEMO_PASSWORD (.env de Docker).
 * Todo se crea a través de los servicios de la aplicación, así que también se
 * generan la actividad y las notificaciones como si lo hubieran hecho los usuarios.
 */
class DemoSeeder extends Seeder
{
    public function run(
        ProjectService $projects,
        MembershipService $members,
        RepositoryService $repositories,
        IssueService $issues,
        PullRequestService $pullRequests,
    ): void {
        $adminEmail = env('SEED_ADMIN_EMAIL', 'admin@codeflow.test');

        if (User::where('email', $adminEmail)->exists()) {
            $this->command?->info('Datos de prueba ya cargados: no se modifica nada.');

            return;
        }

        // En producción no hay contraseñas por defecto (las de abajo están publicadas en el repositorio)
        if (app()->isProduction() && (! env('SEED_ADMIN_PASSWORD') || ! env('SEED_DEMO_PASSWORD') || ! filter_var(env('CODEFLOW_SEED_DEMO'), FILTER_VALIDATE_BOOL))) {
            $this->command?->error('En producción los datos de prueba solo se cargan con CODEFLOW_SEED_DEMO=true y SEED_ADMIN_PASSWORD / SEED_DEMO_PASSWORD definidas.');

            return;
        }

        $adminPassword = env('SEED_ADMIN_PASSWORD', 'Admin-CodeFlow-2026');
        $demoPassword = env('SEED_DEMO_PASSWORD', 'Demo-CodeFlow-2026');

        $admin = $this->user('Administrador', $adminEmail, $adminPassword, admin: true);
        $owner = $this->user('Laura Propietaria', 'propietaria@codeflow.test', $demoPassword);
        $collaborator = $this->user('Carlos Colaborador', 'colaborador@codeflow.test', $demoPassword);
        $observer = $this->user('Olga Observadora', 'observadora@codeflow.test', $demoPassword);

        // Proyecto y miembros (solo la propietaria puede dar acceso)
        $project = $projects->create($owner, [
            'name' => 'Proyecto DAM',
            'description' => 'Proyecto de demostración de CodeFlow.',
        ]);
        $members->add($project, $owner, $collaborator->email, Roles::COLLABORATOR);
        $members->add($project, $owner, $observer->email, Roles::OBSERVER);

        // Repositorio privado con varias ramas y commits
        $api = $repositories->create($project, $owner, [
            'name' => 'codeflow-api',
            'description' => 'API REST del proyecto (privado).',
            'visibility' => 'private',
        ]);
        $main = Branch::where('repository_id', $api->id)->where('name', 'main')->firstOrFail();

        $repositories->commitChanges($main, $owner, 'Estructura inicial del proyecto', [
            ['path' => 'README.md', 'content' => "# codeflow-api\n\nAPI REST del Proyecto DAM.\n\n## Arranque\n\n```\ncomposer install\nphp artisan serve\n```\n"],
            ['path' => 'src/Controllers/UserController.php', 'content' => "<?php\n\nclass UserController\n{\n    public function index()\n    {\n        return User::all();\n    }\n}\n"],
            ['path' => 'src/Models/User.php', 'content' => "<?php\n\nclass User\n{\n    public string \$name;\n    public string \$email;\n}\n"],
            ['path' => 'docs/.keep', 'content' => ''],
        ]);

        $develop = $repositories->createBranch($api, $owner, 'develop', $main->id);
        $feature = $repositories->createBranch($api, $collaborator, 'feature/login', $develop->id);

        $repositories->commitChanges($feature, $collaborator, 'Añadido controlador de login', [
            ['path' => 'src/Controllers/LoginController.php', 'content' => "<?php\n\nclass LoginController\n{\n    public function login(string \$email, string \$password): bool\n    {\n        // TODO: validar credenciales\n        return \$email !== '' && \$password !== '';\n    }\n}\n"],
        ]);
        $repositories->commitChanges($feature->fresh(), $collaborator, 'Paginación en el listado de usuarios', [
            ['path' => 'src/Controllers/UserController.php', 'content' => "<?php\n\nclass UserController\n{\n    public function index()\n    {\n        return User::paginate(20);\n    }\n}\n"],
        ]);

        // Issues
        $bug = $issues->create($api, $owner, [
            'title' => 'El login no valida la contraseña',
            'description' => 'Cualquier contraseña no vacía permite entrar.',
            'priority' => 'critical',
            'assignee_id' => $collaborator->id,
            'labels' => ['bug', 'seguridad'],
        ]);
        $issues->update($bug, $collaborator, ['status' => 'in_progress']);
        $issues->create($api, $collaborator, [
            'title' => 'Documentar los endpoints de usuarios',
            'priority' => 'low',
            'labels' => ['documentación'],
        ]);
        $issues->comment($bug, $observer, 'Desde el cliente confirmamos que es urgente: bloquea la demo del viernes.');

        // Pull Request feature/login → develop con revisión y comentario de observador (alerta)
        $pr = $pullRequests->open($api, $collaborator, [
            'title' => 'Login de usuarios',
            'description' => 'Añade el LoginController y pagina el listado de usuarios.',
            'source_branch_id' => $feature->id,
            'target_branch_id' => $develop->id,
            'reviewers' => [$owner->id],
            'issues' => [$bug->id],
        ]);
        $pullRequests->comment($pr, $observer, '¿Esto incluye el bloqueo tras varios intentos fallidos?');
        $pullRequests->review($pr, $owner, 'changes_requested', 'Falta validar la contraseña con Hash::check().');

        // Repositorio público
        $repositories->create($project, $owner, [
            'name' => 'web-publica',
            'description' => 'Web pública del proyecto (visible sin iniciar sesión).',
            'visibility' => 'public',
        ]);

        $this->command?->info("Usuarios de prueba creados (admin: {$admin->email}).");
    }

    private function user(string $name, string $email, string $password, bool $admin = false): User
    {
        $user = new User;
        $user->forceFill([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
            'is_admin' => $admin,
            'is_active' => true,
        ])->save();

        return $user;
    }
}
