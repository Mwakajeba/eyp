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
        Schema::create('asset_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('asset_id');
            
            // From (previous) state
            $table->unsignedBigInteger('from_branch_id')->nullable();
            $table->unsignedBigInteger('from_department_id')->nullable();
            $table->unsignedBigInteger('from_user_id')->nullable();
            
            // To (new) state
            $table->unsignedBigInteger('to_branch_id')->nullable();
            $table->unsignedBigInteger('to_department_id')->nullable();
            $table->unsignedBigInteger('to_user_id')->nullable();
            
            $table->string('movement_voucher', 50)->unique();
            $table->string('reason')->nullable();
            $table->enum('status', ['draft','pending_review','approved','completed','rejected'])->default('pending_review');
            
            // Timestamps for each stage
            $table->timestamp('initiated_at')->nullable();
            $table->unsignedBigInteger('initiated_by')->nullable();
            
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            
            // GL posting flags
            $table->boolean('gl_post')->default(false);
            $table->boolean('gl_posted')->default(false);
            $table->timestamp('gl_posted_at')->nullable();
            
            $table->text('notes')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_movements');
    }
};
