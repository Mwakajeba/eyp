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
        Schema::table('hr_allowance_types', function (Blueprint $table) {
            $table->dropColumn(['default_amount', 'default_percentage']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_allowance_types', function (Blueprint $table) {
            $table->decimal('default_amount', 15, 2)->nullable();
            $table->decimal('default_percentage', 5, 2)->nullable();
        });
    }
};
