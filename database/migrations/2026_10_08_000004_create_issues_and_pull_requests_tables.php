<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repository_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('number');
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('priority', 20)->default('medium');
            $table->string('status', 20)->default('open');
            $table->json('labels')->nullable();
            $table->timestamps();
            $table->unique(['repository_id', 'number']);
            $table->index(['repository_id', 'status']);
        });

        Schema::create('issue_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issue_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('pull_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repository_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('number');
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('source_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('target_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('source_branch_name', 150);
            $table->string('target_branch_name', 150);
            // open | in_review | approved | changes_requested | closed | merged
            $table->string('status', 20)->default('open');
            $table->unsignedBigInteger('merge_commit_id')->nullable();
            $table->foreignId('merged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('merged_at')->nullable();
            $table->timestamps();
            $table->unique(['repository_id', 'number']);
            $table->index(['repository_id', 'status']);
        });

        Schema::create('pull_request_reviewers', function (Blueprint $table) {
            $table->foreignId('pull_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['pull_request_id', 'user_id']);
        });

        Schema::create('pull_request_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pull_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->string('path', 255)->nullable();
            $table->unsignedInteger('line')->nullable();
            $table->timestamps();
        });

        Schema::create('pull_request_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pull_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('state', 20); // approved | changes_requested | commented
            $table->text('body')->nullable();
            $table->timestamps();
        });

        Schema::create('issue_pull_request', function (Blueprint $table) {
            $table->foreignId('issue_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pull_request_id')->constrained()->cascadeOnDelete();
            $table->primary(['issue_id', 'pull_request_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issue_pull_request');
        Schema::dropIfExists('pull_request_reviews');
        Schema::dropIfExists('pull_request_comments');
        Schema::dropIfExists('pull_request_reviewers');
        Schema::dropIfExists('pull_requests');
        Schema::dropIfExists('issue_comments');
        Schema::dropIfExists('issues');
    }
};
