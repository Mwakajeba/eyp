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
        // Use raw SQL to modify the enum column to include 'completed'
        // MySQL requires ALTER TABLE to modify ENUM columns
        DB::statement("ALTER TABLE `payrolls` MODIFY COLUMN `status` ENUM('draft', 'processing', 'completed', 'paid', 'cancelled') NOT NULL DEFAULT 'draft'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to the previous enum values (without 'completed')
        DB::statement("ALTER TABLE `payrolls` MODIFY COLUMN `status` ENUM('draft', 'processing', 'paid', 'cancelled') NOT NULL DEFAULT 'draft'");
    }
};
