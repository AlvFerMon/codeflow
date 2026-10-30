<?php

namespace App\Models;

use App\Services\EmailVerificationService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Usuario de CodeFlow. Debe verificar su email (MustVerifyEmail): hasta entonces solo puede ver
 * lo público y la pantalla de aviso. Si cambia de email, vuelve a quedar sin verificar.
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token', 'email_verification_code', 'email_verification_expires_at', 'email_verification_attempts', 'onboarding', 'preferences'];

    /** Caché por instancia (y, por tanto, por petición) del rol en cada proyecto. */
    private array $projectRoles = [];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'email_verification_expires_at' => 'datetime',
            'email_verification_attempts' => 'integer',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_active' => 'boolean',
            'onboarding' => 'array',
            'preferences' => 'array',
        ];
    }

    protected static function booted(): void
    {
        // Cambiar el email obliga a verificar la dirección nueva
        static::updating(function (User $user) {
            if ($user->isDirty('email') && ! $user->isDirty('email_verified_at')) {
                $user->email_verified_at = null;
            }
        });

        static::updated(function (User $user) {
            if ($user->wasChanged('email') && ! $user->hasVerifiedEmail()) {
                $user->sendEmailVerificationNotification();
            }
        });
    }

    /** El correo de verificación lleva un código de 6 dígitos (no un enlace). */
    public function sendEmailVerificationNotification(): void
    {
        app(EmailVerificationService::class)->send($this);
    }

    /** Nº de notificaciones sin leer, calculado una sola vez por petición (cabecera y página). */
    public function unreadNotificationsCount(): int
    {
        return once(fn () => $this->unreadNotifications()->count());
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    public function isActive(): bool
    {
        // Si el atributo no está cargado se considera activo (valor por defecto de la columna)
        return $this->is_active ?? true;
    }

    /** Rol del usuario en el proyecto (owner, collaborator, observer) o null si no es miembro. */
    public function roleIn(Project $project): ?string
    {
        if (! array_key_exists($project->id, $this->projectRoles)) {
            $this->projectRoles[$project->id] = ProjectMember::where('project_id', $project->id)
                ->where('user_id', $this->id)
                ->value('role');
        }

        return $this->projectRoles[$project->id];
    }

    /** Vacía la caché de roles (tras cambiar la pertenencia a un proyecto). */
    public function forgetProjectRoles(): void
    {
        $this->projectRoles = [];
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_members')->withPivot('role')->withTimestamps();
    }
}
