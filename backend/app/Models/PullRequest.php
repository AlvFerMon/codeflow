<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PullRequest extends Model
{
    public const OPEN_STATES = ['open', 'in_review', 'approved', 'changes_requested'];

    protected $guarded = [];

    protected $casts = ['merged_at' => 'datetime'];

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN_STATES, true);
    }

    public function repository(): BelongsTo
    {
        return $this->belongsTo(Repository::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function sourceBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'source_branch_id');
    }

    public function targetBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'target_branch_id');
    }

    public function reviewers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'pull_request_reviewers');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(PullRequestComment::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(PullRequestReview::class);
    }

    public function issues(): BelongsToMany
    {
        return $this->belongsToMany(Issue::class, 'issue_pull_request');
    }

    public function activity(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'subject');
    }
}
