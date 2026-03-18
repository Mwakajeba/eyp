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
        Schema::table('provisions', function (Blueprint $table) {
            // Asset linkage fields for Environmental/Decommissioning provisions
            $table->unsignedBigInteger('related_asset_id')->nullable()->after('unwinding_account_id');
            $table->string('asset_category')->nullable()->after('related_asset_id');
            $table->boolean('is_capitalised')->default(false)->after('asset_category');
            $table->date('depreciation_start_date')->nullable()->after('is_capitalised');
            
            // Undiscounted amount (for disclosure purposes)
            $table->decimal('undiscounted_amount', 20, 2)->nullable()->after('original_estimate');
            
            // Discount rate reference (link to central discount rate table)
            $table->unsignedBigInteger('discount_rate_id')->nullable()->after('discount_rate');
            
            // Computation assumptions (JSON for storing calculation inputs)
            $table->json('computation_assumptions')->nullable()->after('estimate_method');
            
            // Foreign key for asset (if assets table exists)
            if (Schema::hasTable('assets')) {
                $table->foreign('related_asset_id')->references('id')->on('assets')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('provisions', function (Blueprint $table) {
            $table->dropForeign(['related_asset_id']);
            $table->dropColumn([
                'related_asset_id',
                'asset_category',
                'is_capitalised',
                'depreciation_start_date',
                'undiscounted_amount',
                'discount_rate_id',
                'computation_assumptions',
            ]);
        });
    }
};

