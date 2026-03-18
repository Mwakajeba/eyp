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
        Schema::table('asset_disposals', function (Blueprint $table) {
            $table->enum('vat_type', ['no_vat', 'exclusive', 'inclusive'])->default('no_vat')->after('vat_amount');
            $table->decimal('vat_rate', 5, 2)->default(0)->after('vat_type');
            $table->boolean('withholding_tax_enabled')->default(false)->after('withholding_tax');
            $table->decimal('withholding_tax_rate', 5, 2)->default(0)->after('withholding_tax_enabled');
            $table->enum('withholding_tax_type', ['percentage', 'fixed'])->default('percentage')->after('withholding_tax_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asset_disposals', function (Blueprint $table) {
            $table->dropColumn(['vat_type', 'vat_rate', 'withholding_tax_enabled', 'withholding_tax_rate', 'withholding_tax_type']);
        });
    }
};
