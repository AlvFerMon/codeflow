<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommitFile extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public function commit(): BelongsTo
    {
        return $this->belongsTo(Commit::class);
    }

    public function blob(): BelongsTo
    {
        return $this->belongsTo(Blob::class);
    }
}
