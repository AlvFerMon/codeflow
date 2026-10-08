<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Commit extends Model
{
    protected $guarded = [];

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
