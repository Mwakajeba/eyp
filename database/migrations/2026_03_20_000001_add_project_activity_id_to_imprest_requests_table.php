<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('imprest_requests', 'project_activity_id')) {
            Schema::table('imprest_requests', function (Blueprint $table) {
                $table->foreignId('project_activity_id')
                    ->nullable()
                    ->after('project_id')
                    ->constrained('project_activities')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('imprest_requests', 'project_activity_id')) {
            Schema::table('imprest_requests', function (Blueprint $table) {
                $table->dropForeign(['project_activity_id']);
                $table->dropColumn('project_activity_id');
            });
        }
    }
};
