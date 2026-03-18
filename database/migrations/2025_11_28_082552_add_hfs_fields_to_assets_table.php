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
        Schema::table('assets', function (Blueprint $table) {
            // HFS status
            $table->enum('hfs_status', ['none', 'pending', 'classified', 'sold', 'cancelled'])->default('none')->after('status');
            
            // Depreciation control
            $table->boolean('depreciation_stopped')->default(false)->after('hfs_status');
            $table->date('depreciation_stopped_date')->nullable()->after('depreciation_stopped');
            $table->text('depreciation_stopped_reason')->nullable()->after('depreciation_stopped_date');
            
            // HFS reference
            $table->unsignedBigInteger('current_hfs_id')->nullable()->after('depreciation_stopped_reason');
            
            // Indexes
            $table->index(['hfs_status']);
            $table->index(['depreciation_stopped']);
            $table->index(['current_hfs_id']);
            
            // Foreign key
            $table->foreign('current_hfs_id')->references('id')->on('hfs_requests')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropForeign(['current_hfs_id']);
            $table->dropIndex(['current_hfs_id']);
            $table->dropIndex(['depreciation_stopped']);
            $table->dropIndex(['hfs_status']);
            $table->dropColumn([
                'hfs_status',
                'depreciation_stopped',
                'depreciation_stopped_date',
                'depreciation_stopped_reason',
                'current_hfs_id'
            ]);
        });
    }
};
