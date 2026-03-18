<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create disposal_reason_codes table if it doesn't exist
        if (!Schema::hasTable('disposal_reason_codes')) {
            Schema::create('disposal_reason_codes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->string('code')->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->enum('disposal_type', ['sale', 'scrap', 'write_off', 'donation', 'loss'])->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_system')->default(false);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
                
                $table->index(['company_id']);
                $table->index(['code']);
                $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            });
        }

        // Create disposal_approvals table if it doesn't exist
        if (!Schema::hasTable('disposal_approvals')) {
            Schema::create('disposal_approvals', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('disposal_id');
                $table->enum('approval_level', ['department_head', 'finance_manager', 'cfo', 'board'])->default('department_head');
                $table->enum('status', ['pending', 'approved', 'rejected', 'requested_modification'])->default('pending');
                $table->text('comments')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->unsignedBigInteger('approver_id')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('rejected_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                
                $table->index(['disposal_id']);
                $table->index(['approval_level', 'status']);
                $table->foreign('disposal_id')->references('id')->on('asset_disposals')->onDelete('cascade');
                $table->foreign('approver_id')->references('id')->on('users')->onDelete('set null');
            });
        }

        // Add foreign key if it doesn't exist
        if (Schema::hasTable('asset_disposals') && Schema::hasTable('disposal_reason_codes')) {
            if (DB::getDriverName() === 'mysql') {
                $foreignKeys = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'asset_disposals' AND COLUMN_NAME = 'disposal_reason_code_id'");
            } else {
                // For SQLite, check if foreign key exists differently
                $foreignKeys = DB::select("PRAGMA foreign_key_list(asset_disposals)");
                $foreignKeys = array_filter($foreignKeys, fn($fk) => $fk->from === 'disposal_reason_code_id');
            }
            
            if (empty($foreignKeys)) {
                Schema::table('asset_disposals', function (Blueprint $table) {
                    $table->foreign('disposal_reason_code_id')->references('id')->on('disposal_reason_codes')->onDelete('set null');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Don't drop tables in down() to avoid data loss
    }
};

