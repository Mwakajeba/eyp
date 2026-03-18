<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->enum('default_depreciation_method', ['straight_line', 'declining_balance', 'syd', 'units'])->default('straight_line');
            $table->unsignedInteger('default_useful_life_months')->default(60);
            $table->decimal('default_depreciation_rate', 10, 2)->default(0);
            $table->enum('depreciation_convention', ['monthly_prorata', 'mid_month', 'full_month'])->default('monthly_prorata');
            $table->decimal('capitalization_threshold', 18, 2)->default(0);
            $table->unsignedBigInteger('asset_account_id')->nullable();
            $table->unsignedBigInteger('accum_depr_account_id')->nullable();
            $table->unsignedBigInteger('depr_expense_account_id')->nullable();
            $table->unsignedBigInteger('gain_on_disposal_account_id')->nullable();
            $table->unsignedBigInteger('loss_on_disposal_account_id')->nullable();
            $table->unsignedBigInteger('revaluation_reserve_account_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_categories');
    }
};


