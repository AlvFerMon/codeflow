<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    /** Caché por instancia (y, por tanto, por petición) del rol en cada proyecto. */
    private array $projectRoles = [];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_active' => 'boolean',
        ];
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
