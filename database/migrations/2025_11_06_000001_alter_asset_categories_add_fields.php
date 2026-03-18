<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_categories', function (Blueprint $table) {
            $table->decimal('residual_value_percent', 5, 2)->default(0)->after('capitalization_threshold');
            $table->string('ifrs_reference')->nullable()->after('residual_value_percent');
            $table->text('notes')->nullable()->after('ifrs_reference');
        });
    }

    public function down(): void
    {
        Schema::table('asset_categories', function (Blueprint $table) {
            $table->dropColumn(['residual_value_percent', 'ifrs_reference', 'notes']);
        });
    }
};


