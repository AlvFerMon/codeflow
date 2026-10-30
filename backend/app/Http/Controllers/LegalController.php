<?php

namespace App\Http\Controllers;

use App\Services\InvitationService;
use App\Services\TrashService;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Páginas legales (privacidad, cookies, condiciones, aviso legal). Públicas, sin sesión.
 *
 * El texto está en resources/legal/*.md (fácil de editar) y los datos del titular en config('codeflow.legal').
 */
class LegalController extends Controller
{
    public const PAGES = [
        'privacidad' => 'Política de privacidad',
        'cookies' => 'Política de cookies',
        'terminos' => 'Condiciones de uso',
        'aviso-legal' => 'Aviso legal',
    ];

    public function __invoke(string $page): View
    {
        abort_unless(isset(self::PAGES[$page]), 404);

        $legal = config('codeflow.legal');
        $email = filled($legal['email']) ? $legal['email'] : 'nuestro correo de contacto (lo publicaremos aquí en breve)';
        $values = [
            '{owner}' => $legal['owner'],
            '{center}' => filled($legal['center']) ? ', '.$legal['center'] : '',
            '{email}' => $email,
            '{updated}' => $legal['updated'],
            '{app_region}' => $legal['app_region'],
            '{db_region}' => $legal['db_region'],
            '{trash_days}' => (string) TrashService::DAYS,
            '{invitation_days}' => (string) InvitationService::DAYS,
            '{session_cookie}' => (string) config('session.cookie'),
            '{session_minutes}' => (string) config('session.lifetime'),
        ];
        // Los valores vienen de la configuración, pero se escapan igual (el Markdown no admite HTML)
        $markdown = strtr((string) file_get_contents(resource_path("legal/{$page}.md")), $values);

        return view('legal.show', [
            'title' => self::PAGES[$page],
            'page' => $page,
            'html' => Str::markdown($markdown, ['html_input' => 'escape', 'allow_unsafe_links' => false]),
        ]);
    }
}
