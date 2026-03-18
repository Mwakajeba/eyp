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
        Schema::table('hfs_requests', function (Blueprint $table) {
            // Drop the old supplier foreign key and column
            $table->dropForeign(['supplier_id']);
            $table->dropColumn('supplier_id');
            
            // Add customer_id column
            $table->unsignedBigInteger('customer_id')->nullable()->after('buyer_name');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hfs_requests', function (Blueprint $table) {
            // Drop customer foreign key and column
            $table->dropForeign(['customer_id']);
            $table->dropColumn('customer_id');
            
            // Restore supplier_id
            $table->unsignedBigInteger('supplier_id')->nullable()->after('buyer_name');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('set null');
        });
    }
};
