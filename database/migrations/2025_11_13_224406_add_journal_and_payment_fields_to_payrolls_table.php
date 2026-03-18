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
        Schema::table('payrolls', function (Blueprint $table) {
            // Journal tracking
            if (!Schema::hasColumn('payrolls', 'journal_reference')) {
                $table->string('journal_reference')->nullable();
            }
            
            // Payment tracking (some fields already exist from previous migration)
            if (!Schema::hasColumn('payrolls', 'payment_status')) {
                $table->enum('payment_status', ['pending', 'paid'])->default('pending');
            }
            if (!Schema::hasColumn('payrolls', 'payment_reference')) {
                $table->string('payment_reference')->nullable();
            }
            if (!Schema::hasColumn('payrolls', 'payment_journal_reference')) {
                $table->string('payment_journal_reference')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            if (Schema::hasColumn('payrolls', 'journal_reference')) {
                $table->dropColumn('journal_reference');
            }
            if (Schema::hasColumn('payrolls', 'payment_status')) {
                $table->dropColumn('payment_status');
            }
            if (Schema::hasColumn('payrolls', 'payment_reference')) {
                $table->dropColumn('payment_reference');
            }
            if (Schema::hasColumn('payrolls', 'payment_journal_reference')) {
                $table->dropColumn('payment_journal_reference');
            }
        });
    }
};
