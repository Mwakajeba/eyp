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
        if (!Schema::hasColumn('loans', 'capitalisation_end_date')) {
            Schema::table('loans', function (Blueprint $table) {
                $table->date('capitalisation_end_date')->nullable()->after('capitalise_interest');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('loans', 'capitalisation_end_date')) {
            Schema::table('loans', function (Blueprint $table) {
                $table->dropColumn('capitalisation_end_date');
            });
        }
    }
};



