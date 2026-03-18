<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add 'pending_approval' to rental_quotations.status enum for approval workflow.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE rental_quotations MODIFY COLUMN status ENUM('draft', 'pending_approval', 'sent', 'approved', 'rejected', 'expired', 'converted') DEFAULT 'draft'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Optionally convert any pending_approval back to draft before reverting enum
        DB::table('rental_quotations')->where('status', 'pending_approval')->update(['status' => 'draft']);
        DB::statement("ALTER TABLE rental_quotations MODIFY COLUMN status ENUM('draft', 'sent', 'approved', 'rejected', 'expired', 'converted') DEFAULT 'draft'");
    }
};
