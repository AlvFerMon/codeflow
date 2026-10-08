<?php

namespace App\Vcs\Crypto;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Cifrado en reposo por repositorio.
 *
 * La DEK de cada repositorio se descifra una sola vez por instancia (singleton)
 * y solo vive en memoria. De ella se derivan con HKDF dos claves independientes:
 * una para AES-256-GCM (contenido) y otra para HMAC-SHA256 (deduplicación).
 * Nunca se registran claves en logs ni en caché.
 */
class RepositoryCipher
{
    /** @var array<int,array{enc:Encrypter,mac:string}> */
    private array $keys = [];

    public function __construct(private readonly KeyManager $keyManager) {}

    /** Comprime y cifra (seguro para binarios). */
    public function encrypt(int $repositoryId, string $plain): string
    {
        $compressed = gzcompress($plain);

        return $this->keys($repositoryId)['enc']->encryptString(base64_encode($compressed));
    }

    public function decrypt(int $repositoryId, string $payload): string
    {
        try {
            $compressed = base64_decode($this->keys($repositoryId)['enc']->decryptString($payload), true);
        } catch (DecryptException) {
            throw new RuntimeException("No se pudo descifrar un objeto del repositorio {$repositoryId}: datos manipulados o clave incorrecta.");
        }

        $plain = $compressed === false ? false : @gzuncompress($compressed);

        if ($plain === false) {
            throw new RuntimeException("Objeto dañado en el repositorio {$repositoryId}.");
        }

        return $plain;
    }

    /** HMAC-SHA256 (hex) del contenido con la clave del repositorio. */
    public function mac(int $repositoryId, string $plain): string
    {
        return hash_hmac('sha256', $plain, $this->keys($repositoryId)['mac']);
    }

    /** Olvida la clave memorizada (p. ej. al borrar el repositorio). */
    public function forget(int $repositoryId): void
    {
        unset($this->keys[$repositoryId]);
    }

    /** @return array{enc:Encrypter,mac:string} */
    private function keys(int $repositoryId): array
    {
        if (isset($this->keys[$repositoryId])) {
            return $this->keys[$repositoryId];
        }

        $encryptedKey = DB::table('repositories')->where('id', $repositoryId)->value('encrypted_key');

        if (! $encryptedKey) {
            throw new RuntimeException("El repositorio {$repositoryId} no existe o no tiene clave de cifrado.");
        }

        $dek = $this->keyManager->decryptKey($encryptedKey);

        return $this->keys[$repositoryId] = [
            'enc' => new Encrypter(hash_hkdf('sha256', $dek, 32, 'codeflow:enc'), 'aes-256-gcm'),
            'mac' => hash_hkdf('sha256', $dek, 32, 'codeflow:mac'),
        ];
    }
}
