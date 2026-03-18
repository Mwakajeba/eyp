<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('imprest_requests', 'project_id')) {
            Schema::table('imprest_requests', function (Blueprint $table) {
                $table->foreignId('project_id')
                    ->nullable()
                    ->after('department_id')
                    ->constrained('projects')
                    ->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('imprest_requests', 'project_id')) {
            Schema::table('imprest_requests', function (Blueprint $table) {
                $table->dropConstrainedForeignId('project_id');
            });
        }
    }
};
