<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Revisión con IA de un pull request (sin código: solo resumen y hallazgos). */
class AiAnalysis extends Model
{
    public const PENDING = 'pending';

    public const RUNNING = 'running';

    public const DONE = 'done';

    public const FAILED = 'failed';

    protected $guarded = [];

    protected $casts = ['started_at' => 'datetime', 'finished_at' => 'datetime'];

    public function pullRequest(): BelongsTo
    {
        return $this->belongsTo(PullRequest::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function findings(): HasMany
    {
        // Primero los más graves; dentro de cada gravedad, por archivo y línea
        return $this->hasMany(AiFinding::class)
            ->orderByRaw("case severity when 'high' then 0 when 'medium' then 1 else 2 end")
            ->orderBy('path')
            ->orderBy('line');
    }

    public function isInProgress(): bool
    {
        return in_array($this->status, [self::PENDING, self::RUNNING], true);
    }
}
