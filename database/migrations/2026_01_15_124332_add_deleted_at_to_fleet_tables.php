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
        // Add deleted_at column to all fleet tables that use SoftDeletes
        Schema::table('fleet_cost_categories', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('fleet_approval_workflows', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('fleet_workflow_approvers', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fleet_cost_categories', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('fleet_approval_workflows', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('fleet_workflow_approvers', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
