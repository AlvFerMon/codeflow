<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use SoftDeletes; // papelera (ver TrashService)

    protected $guarded = [];

    protected $casts = ['settings' => 'array'];

    /** ¿El usuario pertenece al proyecto (con cualquier rol)? */
    public function hasMember(int $userId): bool
    {
        return ProjectMember::where('project_id', $this->id)->where('user_id', $userId)->exists();
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_members')->withPivot('role')->withTimestamps();
    }

    public function repositories(): HasMany
    {
        return $this->hasMany(Repository::class);
    }

    /** Issues de todos los repositorios del proyecto. */
    public function issues(): HasManyThrough
    {
        return $this->hasManyThrough(Issue::class, Repository::class);
    }

    /** Pull requests de todos los repositorios del proyecto. */
    public function pullRequests(): HasManyThrough
    {
        return $this->hasManyThrough(PullRequest::class, Repository::class);
    }

    public function activity(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }
}
