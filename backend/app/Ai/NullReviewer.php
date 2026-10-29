<?php

namespace App\Ai;

/** Se usa cuando la IA está desactivada (CODEFLOW_AI_ENABLED=false). */
class NullReviewer implements AiReviewer
{
    public function review(string $diff, array $paths): ReviewResult
    {
        throw new AiUnavailableException('La revisión con IA está desactivada.');
    }

    public function model(): string
    {
        return 'ninguno';
    }
}
