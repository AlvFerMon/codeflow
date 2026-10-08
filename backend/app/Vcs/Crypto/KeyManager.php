<?php

namespace App\Vcs\Crypto;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Encryption\Encrypter;
use RuntimeException;

/**
 * Gestiona las claves de repositorio (DEK) con cifrado de sobre:
 * cada DEK son 32 bytes aleatorios cifrados con la clave maestra (KEK).
 */
class KeyManager
{
    private const CIPHER = 'aes-256-gcm';

    private ?Encrypter $encrypter = null;

    public function __construct(
        private readonly ?string $masterKey,
        private readonly int $keyVersion = 1,
    ) {}

    /** Versión de la clave maestra actual. */
    public function version(): int
    {
        return $this->keyVersion;
    }

    /** Genera una DEK nueva y la devuelve cifrada con la clave maestra. */
    public function generateEncryptedKey(): string
    {
        return $this->encrypter()->encryptString(base64_encode(random_bytes(32)));
    }

    /** Descifra una DEK y devuelve sus 32 bytes en bruto. */
    public function decryptKey(string $encryptedKey): string
    {
        try {
            $dek = base64_decode($this->encrypter()->decryptString($encryptedKey), true);
        } catch (DecryptException) {
            throw new RuntimeException('No se pudo descifrar la clave del repositorio: la clave maestra no coincide o los datos están dañados.');
        }

        if ($dek === false || strlen($dek) !== 32) {
            throw new RuntimeException('La clave del repositorio descifrada no es válida.');
        }

        return $dek;
    }

    private function encrypter(): Encrypter
    {
        return $this->encrypter ??= new Encrypter($this->parseMasterKey(), self::CIPHER);
    }

    private function parseMasterKey(): string
    {
        $value = trim((string) $this->masterKey);

        if ($value === '') {
            throw new RuntimeException('Falta la clave maestra CODEFLOW_MASTER_KEY en el .env. Genérala con: php -r "echo \'base64:\'.base64_encode(random_bytes(32));"');
        }

        $key = str_starts_with($value, 'base64:') ? base64_decode(substr($value, 7), true) : false;

        if ($key === false || strlen($key) !== 32) {
            throw new RuntimeException('La clave maestra CODEFLOW_MASTER_KEY no es válida: debe tener el formato "base64:..." y contener 32 bytes.');
        }

        return $key;
    }
}
