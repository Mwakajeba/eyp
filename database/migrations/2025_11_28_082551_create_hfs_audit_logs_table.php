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
        Schema::create('hfs_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hfs_id');
            
            // Action details
            $table->string('action'); // created, updated, approved, rejected, reclassified, measured, sold, cancelled, etc.
            $table->string('action_type')->nullable(); // approval, valuation, disposal, etc.
            
            // User and timestamp
            $table->unsignedBigInteger('user_id')->nullable();
            $table->dateTime('action_date');
            
            // Change details
            $table->json('old_values')->nullable(); // Previous state
            $table->json('new_values')->nullable(); // New state
            $table->text('description')->nullable();
            
            // Related entities
            $table->unsignedBigInteger('related_id')->nullable(); // Related approval, valuation, disposal, etc.
            $table->string('related_type')->nullable(); // hfs_approval, hfs_valuation, hfs_disposal, etc.
            
            // IP and user agent (for security)
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            
            // Notes
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['hfs_id']);
            $table->index(['action']);
            $table->index(['action_type']);
            $table->index(['user_id']);
            $table->index(['action_date']);
            $table->index(['related_id', 'related_type']);
            
            // Foreign keys
            $table->foreign('hfs_id')->references('id')->on('hfs_requests')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hfs_audit_logs');
    }
};
