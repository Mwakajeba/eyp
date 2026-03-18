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
        Schema::table('payroll_audit_logs', function (Blueprint $table) {
            // Check and add columns if they don't exist
            if (!Schema::hasColumn('payroll_audit_logs', 'payroll_id')) {
                $table->foreignId('payroll_id')->after('id')->constrained('payrolls')->onDelete('cascade');
            }
            if (!Schema::hasColumn('payroll_audit_logs', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('payroll_id')->constrained('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('payroll_audit_logs', 'action')) {
                $table->string('action')->after('user_id');
            }
            if (!Schema::hasColumn('payroll_audit_logs', 'field_name')) {
                $table->string('field_name')->nullable()->after('action');
            }
            if (!Schema::hasColumn('payroll_audit_logs', 'old_value')) {
                $table->text('old_value')->nullable()->after('field_name');
            }
            if (!Schema::hasColumn('payroll_audit_logs', 'new_value')) {
                $table->text('new_value')->nullable()->after('old_value');
            }
            if (!Schema::hasColumn('payroll_audit_logs', 'description')) {
                $table->text('description')->nullable()->after('new_value');
            }
            if (!Schema::hasColumn('payroll_audit_logs', 'remarks')) {
                $table->text('remarks')->nullable()->after('description');
            }
            if (!Schema::hasColumn('payroll_audit_logs', 'ip_address')) {
                $table->string('ip_address')->nullable()->after('remarks');
            }
            if (!Schema::hasColumn('payroll_audit_logs', 'user_agent')) {
                $table->text('user_agent')->nullable()->after('ip_address');
            }
            if (!Schema::hasColumn('payroll_audit_logs', 'metadata')) {
                $table->json('metadata')->nullable()->after('user_agent');
            }
        });

        // Add indexes - Laravel will handle if they already exist
        try {
            Schema::table('payroll_audit_logs', function (Blueprint $table) {
                $table->index('payroll_id');
            });
        } catch (\Exception $e) {
            // Index might already exist, ignore
        }
        
        try {
            Schema::table('payroll_audit_logs', function (Blueprint $table) {
                $table->index('user_id');
            });
        } catch (\Exception $e) {
            // Index might already exist, ignore
        }
        
        try {
            Schema::table('payroll_audit_logs', function (Blueprint $table) {
                $table->index('action');
            });
        } catch (\Exception $e) {
            // Index might already exist, ignore
        }
        
        try {
            Schema::table('payroll_audit_logs', function (Blueprint $table) {
                $table->index('created_at');
            });
        } catch (\Exception $e) {
            // Index might already exist, ignore
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_audit_logs', function (Blueprint $table) {
            // Drop indexes first (ignore errors if they don't exist)
            try {
                $table->dropIndex('payroll_audit_logs_created_at_index');
            } catch (\Exception $e) {}
            
            try {
                $table->dropIndex('payroll_audit_logs_action_index');
            } catch (\Exception $e) {}
            
            try {
                $table->dropIndex('payroll_audit_logs_user_id_index');
            } catch (\Exception $e) {}
            
            try {
                $table->dropIndex('payroll_audit_logs_payroll_id_index');
            } catch (\Exception $e) {}

            // Drop foreign keys and columns
            if (Schema::hasColumn('payroll_audit_logs', 'metadata')) {
                $table->dropColumn('metadata');
            }
            if (Schema::hasColumn('payroll_audit_logs', 'user_agent')) {
                $table->dropColumn('user_agent');
            }
            if (Schema::hasColumn('payroll_audit_logs', 'ip_address')) {
                $table->dropColumn('ip_address');
            }
            if (Schema::hasColumn('payroll_audit_logs', 'remarks')) {
                $table->dropColumn('remarks');
            }
            if (Schema::hasColumn('payroll_audit_logs', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('payroll_audit_logs', 'new_value')) {
                $table->dropColumn('new_value');
            }
            if (Schema::hasColumn('payroll_audit_logs', 'old_value')) {
                $table->dropColumn('old_value');
            }
            if (Schema::hasColumn('payroll_audit_logs', 'field_name')) {
                $table->dropColumn('field_name');
            }
            if (Schema::hasColumn('payroll_audit_logs', 'action')) {
                $table->dropColumn('action');
            }
            if (Schema::hasColumn('payroll_audit_logs', 'user_id')) {
                try {
                    $table->dropForeign(['user_id']);
                } catch (\Exception $e) {}
                $table->dropColumn('user_id');
            }
            if (Schema::hasColumn('payroll_audit_logs', 'payroll_id')) {
                try {
                    $table->dropForeign(['payroll_id']);
                } catch (\Exception $e) {}
                $table->dropColumn('payroll_id');
            }
        });
    }
};
