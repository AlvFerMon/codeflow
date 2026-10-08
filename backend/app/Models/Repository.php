<?php

namespace App\Models;

use App\Vcs\Crypto\KeyManager;
use App\Vcs\Crypto\RepositoryCipher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Repository extends Model
{
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

    public function isPublic(): bool
    {
        return $this->visibility === self::VISIBILITY_PUBLIC;
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
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
