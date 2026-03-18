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
        // For MySQL, we need to alter the enum column
        DB::statement("ALTER TABLE `rental_customer_deposits` MODIFY COLUMN `status` ENUM('draft', 'pending', 'confirmed', 'refunded', 'applied') DEFAULT 'draft'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original enum values
        DB::statement("ALTER TABLE `rental_customer_deposits` MODIFY COLUMN `status` ENUM('pending', 'confirmed', 'refunded', 'applied') DEFAULT 'pending'");
    }
};
