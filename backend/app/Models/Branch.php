<?php

namespace App\Models;

use App\Models\Concerns\BelongsToActiveRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Branch extends Model
{
    use BelongsToActiveRepository;

    protected $guarded = [];

    public function repository(): BelongsTo
    {
        return $this->belongsTo(Repository::class);
    }

    public function headCommit(): BelongsTo
    {
        return $this->belongsTo(Commit::class, 'head_commit_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
