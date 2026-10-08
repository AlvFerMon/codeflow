<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

final class Activity
{
    public static function log(Project $project, ?User $user, string $action, ?Model $subject = null, array $properties = []): void
    {
        ActivityLog::create([
            'project_id' => $project->id,
            'user_id' => $user?->id,
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'properties' => $properties ?: null,
        ]);
    }
}
