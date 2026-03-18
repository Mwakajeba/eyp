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
        Schema::table('asset_categories', function (Blueprint $table) {
            // HFS control account (per category as recommended)
            $table->unsignedBigInteger('hfs_account_id')->nullable()->after('accumulated_impairment_account_id');
            
            // Index
            $table->index(['hfs_account_id']);
            
            // Foreign key
            $table->foreign('hfs_account_id')->references('id')->on('chart_accounts')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asset_categories', function (Blueprint $table) {
            $table->dropForeign(['hfs_account_id']);
            $table->dropIndex(['hfs_account_id']);
            $table->dropColumn('hfs_account_id');
        });
    }
};
