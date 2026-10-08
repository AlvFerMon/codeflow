<?php

namespace App\Models;

use App\Casts\RepositoryEncrypted;
use App\Models\Concerns\OrdersRepositoryAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Contenido de un archivo, cifrado con la clave del repositorio y
 * deduplicado por HMAC (content_mac). $blob->content devuelve el texto en claro.
 */
class Blob extends Model
{
    use OrdersRepositoryAttributes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'content' => RepositoryEncrypted::class,
            'size' => 'integer',
            'is_binary' => 'boolean',
        ];
    }

    public function repository(): BelongsTo
    {
        return $this->belongsTo(Repository::class);
    }
}
