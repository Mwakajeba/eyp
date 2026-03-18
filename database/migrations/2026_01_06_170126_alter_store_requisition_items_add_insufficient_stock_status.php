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
        // Modify the enum to include 'insufficient_stock' status
        DB::statement("ALTER TABLE store_requisition_items MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'partially_issued', 'fully_issued', 'insufficient_stock') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original enum without 'insufficient_stock'
        DB::statement("ALTER TABLE store_requisition_items MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'partially_issued', 'fully_issued') DEFAULT 'pending'");
    }
};
