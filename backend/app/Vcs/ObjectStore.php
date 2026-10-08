<?php

namespace App\Vcs;

use App\Models\Blob;
use App\Vcs\Crypto\RepositoryCipher;
use Illuminate\Support\Facades\DB;

/**
 * Almacén de contenidos (blobs) de un repositorio.
 *
 * El contenido se guarda comprimido y cifrado (cast RepositoryEncrypted) y se
 * deduplica por HMAC-SHA256 con la clave del repositorio: el mismo archivo en
 * dos repositorios distintos produce MAC distintos y no se comparte.
 */
class ObjectStore
{
    /** Bytes que se inspeccionan para detectar un binario (igual que Git). */
    private const BINARY_SNIFF_BYTES = 8000;

    public function __construct(private readonly RepositoryCipher $cipher) {}

    /** Guarda (o reutiliza) el blob de un contenido. */
    public function put(int $repositoryId, string $content, ?string $path = null): Blob
    {
        $this->assertSize($content, $path);

        $mac = $this->cipher->mac($repositoryId, $content);

        $blob = Blob::where('repository_id', $repositoryId)->where('content_mac', $mac)->first();
        if ($blob) {
            return $blob;
        }

        // createOrFirst cubre la carrera entre dos commits simultáneos del mismo repositorio
        $blob = Blob::createOrFirst(
            ['repository_id' => $repositoryId, 'content_mac' => $mac],
            ['content' => $content, 'size' => strlen($content), 'is_binary' => $this->isBinary($content)]
        );

        if ($blob->wasRecentlyCreated) {
            DB::table('repositories')->where('id', $repositoryId)->increment('size_bytes', $blob->size);
        }

        return $blob;
    }

    /**
     * Lee un blob del repositorio (descifra solo lo pedido).
     *
     * @return array{content:string,size:int,binary:bool}
     */
    public function read(int $repositoryId, int $blobId): array
    {
        $blob = Blob::where('repository_id', $repositoryId)->findOrFail($blobId);

        return ['content' => $blob->content, 'size' => (int) $blob->size, 'binary' => (bool) $blob->is_binary];
    }

    /** Binario si contiene un byte NUL al principio o no es UTF-8 válido. */
    public function isBinary(string $content): bool
    {
        return str_contains(substr($content, 0, self::BINARY_SNIFF_BYTES), "\0")
            || ! mb_check_encoding($content, 'UTF-8');
    }

    public function assertSize(string $content, ?string $path = null): void
    {
        $max = (int) config('codeflow.limits.max_file_bytes');

        if ($max > 0 && strlen($content) > $max) {
            $limit = round($max / 1048576, 2).' MB';
            abort(422, $path
                ? "El archivo {$path} supera el tamaño máximo permitido ({$limit})."
                : "El archivo supera el tamaño máximo permitido ({$limit}).");
        }
    }
}
