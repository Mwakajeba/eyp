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
        Schema::table('budgets', function (Blueprint $table) {
            if (! Schema::hasColumn('budgets', 'start_date')) {
                $table->date('start_date')->nullable()->after('year');
            }

            if (! Schema::hasColumn('budgets', 'end_date')) {
                $table->date('end_date')->nullable()->after('start_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            if (Schema::hasColumn('budgets', 'end_date')) {
                $table->dropColumn('end_date');
            }

            if (Schema::hasColumn('budgets', 'start_date')) {
                $table->dropColumn('start_date');
            }
        });
    }
};
