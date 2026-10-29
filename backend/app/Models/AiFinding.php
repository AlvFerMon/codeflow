<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Un hallazgo de la revisión con IA: archivo, línea, gravedad, categoría y explicación. */
class AiFinding extends Model
{
    public const SEVERITIES = ['high', 'medium', 'low'];

    public const CATEGORIES = ['bug', 'security', 'performance', 'style', 'maintainability'];

    protected $guarded = [];

    public function analysis(): BelongsTo
    {
        return $this->belongsTo(AiAnalysis::class, 'ai_analysis_id');
    }
}
