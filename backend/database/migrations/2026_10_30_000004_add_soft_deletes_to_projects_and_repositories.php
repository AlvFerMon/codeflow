<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Papelera: los proyectos y repositorios borrados se guardan 30 días y se pueden restaurar. */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['projects', 'repositories'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->softDeletes();
                $t->index('deleted_at');
            });
        }
    }

    public function down(): void
    {
        foreach (['projects', 'repositories'] as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                $t->dropIndex("{$table}_deleted_at_index");
                $t->dropSoftDeletes();
            });
        }
    }
};
