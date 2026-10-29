<?php

namespace App\Models;

use App\Vcs\Crypto\KeyManager;
use App\Vcs\Crypto\RepositoryCipher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Repository extends Model
{
    use SoftDeletes; // papelera (ver TrashService)

    public const VISIBILITY_PRIVATE = 'private';

    public const VISIBILITY_PUBLIC = 'public';

    public const VISIBILITIES = [self::VISIBILITY_PRIVATE, self::VISIBILITY_PUBLIC];

    /** La clave cifrada solo la gestiona el modelo (evento "creating"). */
    protected $guarded = ['id', 'encrypted_key', 'key_version'];

    protected $hidden = ['encrypted_key'];

    protected $attributes = [
        'visibility' => self::VISIBILITY_PRIVATE,
        'size_bytes' => 0,
    ];

    protected function casts(): array
    {
        return [
            'key_version' => 'integer',
            'size_bytes' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Cada repositorio nace con su propia clave (DEK) cifrada con la clave maestra
        static::creating(function (Repository $repository) {
            if (empty($repository->encrypted_key)) {
                $keys = app(KeyManager::class);
                $repository->encrypted_key = $keys->generateEncryptedKey();
                $repository->key_version = $keys->version();
            }
        });

        static::deleted(fn (Repository $repository) => app(RepositoryCipher::class)->forget($repository->id));
    }

    /**
     * Columnas que muestran los listados (sin la clave cifrada) + fecha y sha del último commit
     * mediante subconsultas que usan el índice (repository_id, created_at).
     */
    public function scopeForListing(Builder $query): Builder
    {
        $lastCommit = fn (string $column) => Commit::select($column)
            ->whereColumn('repository_id', 'repositories.id')
            ->latest('created_at')
            ->limit(1);

        return $query
            ->select(['repositories.id', 'repositories.project_id', 'repositories.name', 'repositories.description',
                'repositories.default_branch', 'repositories.visibility', 'repositories.created_at', 'repositories.updated_at'])
            ->addSelect(['last_commit_at' => $lastCommit('created_at'), 'last_commit_sha' => $lastCommit('sha')])
            ->withCasts(['last_commit_at' => 'datetime']);
    }

    public function isPublic(): bool
    {
        return $this->visibility === self::VISIBILITY_PUBLIC;
    }

    public function project(): BelongsTo
    {
        // También si el proyecto está en la papelera (para restaurar o vaciar)
        return $this->belongsTo(Project::class)->withTrashed();
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function commits(): HasMany
    {
        return $this->hasMany(Commit::class);
    }

    public function blobs(): HasMany
    {
        return $this->hasMany(Blob::class);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class);
    }

    public function pullRequests(): HasMany
    {
        return $this->hasMany(PullRequest::class);
    }
}
