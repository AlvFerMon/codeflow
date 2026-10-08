<?php

namespace App\Vcs;

/**
 * Identificador de un commit: SHA-256 de una representación JSON canónica de
 * su contenido (árbol, padres, autor, mensaje y fecha). Es determinista, así
 * que cualquiera puede recalcularlo para comprobar la integridad del commit.
 */
class CommitHasher
{
    /** Formato de la fecha incluida en el hash (igual que Commit::$dateFormat). */
    public const DATE_FORMAT = 'Y-m-d H:i:s.u';

    /**
     * @param  array<string,string>  $tree  ruta => content_mac del blob
     * @param  array<int,string>  $parentShas  en orden (primer padre, segundo padre)
     */
    public function hash(array $tree, array $parentShas, ?int $authorId, string $message, string $timestamp): string
    {
        $entries = [];
        foreach ($tree as $path => $mac) {
            $entries[] = [(string) $path, (string) $mac];
        }
        usort($entries, fn ($a, $b) => strcmp($a[0], $b[0]));

        $canonical = json_encode([
            'tree' => $entries,
            'parents' => array_values($parentShas),
            'author' => $authorId,
            'message' => $message,
            'timestamp' => $timestamp,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);

        return hash('sha256', $canonical);
    }
}
