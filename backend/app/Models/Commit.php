<?php

namespace App\Models;

use App\Casts\RepositoryEncrypted;
use App\Models\Concerns\OrdersRepositoryAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Nodo del grafo de commits. El mensaje se guarda cifrado y "sha" es el
 * SHA-256 del contenido del commit (ver App\Vcs\CommitHasher).
 */
class Commit extends Model
{
    use OrdersRepositoryAttributes;

    protected $guarded = [];

    /** Microsegundos: la fecha forma parte del hash del commit. */
    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected function casts(): array
    {
        return [
            'message' => RepositoryEncrypted::class,
        ];
    }

    public function repository(): BelongsTo
    {
        return $this->belongsTo(Repository::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(CommitFile::class);
    }
}
