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
        Schema::create('maintenance_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('branch_id')->nullable(); // NULL = company-wide
            $table->string('setting_key');
            $table->string('setting_name');
            $table->text('setting_value')->nullable();
            $table->text('description')->nullable();
            $table->enum('setting_type', ['text', 'number', 'decimal', 'boolean', 'json', 'chart_account_id'])->default('text');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            // Unique constraint: setting_key must be unique per company and branch
            $table->unique(['company_id', 'branch_id', 'setting_key'], 'maintenance_settings_unique');
            $table->index(['company_id', 'branch_id', 'setting_key']);
        });

        // Insert default settings for all existing companies
        $companies = DB::table('companies')->pluck('id');
        
        if ($companies->isNotEmpty()) {
            $defaultSettings = [];
            foreach ($companies as $companyId) {
                $defaultSettings[] = [
                    'company_id' => $companyId,
                    'branch_id' => null,
                    'setting_key' => 'maintenance_expense_account',
                    'setting_name' => 'Maintenance Expense Account',
                    'setting_value' => null,
                    'description' => 'Default GL account for routine maintenance expenses',
                    'setting_type' => 'chart_account_id',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $defaultSettings[] = [
                    'company_id' => $companyId,
                    'branch_id' => null,
                    'setting_key' => 'maintenance_wip_account',
                    'setting_name' => 'Maintenance Work-in-Progress Account',
                    'setting_value' => null,
                    'description' => 'GL account for maintenance WIP during execution',
                    'setting_type' => 'chart_account_id',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $defaultSettings[] = [
                    'company_id' => $companyId,
                    'branch_id' => null,
                    'setting_key' => 'asset_capitalization_account',
                    'setting_name' => 'Asset Capitalization Account',
                    'setting_value' => null,
                    'description' => 'GL account for capitalized maintenance costs',
                    'setting_type' => 'chart_account_id',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $defaultSettings[] = [
                    'company_id' => $companyId,
                    'branch_id' => null,
                    'setting_key' => 'capitalization_threshold_amount',
                    'setting_name' => 'Capitalization Threshold Amount',
                    'setting_value' => '2000000',
                    'description' => 'Minimum maintenance cost amount (TZS) to qualify for capitalization',
                    'setting_type' => 'decimal',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $defaultSettings[] = [
                    'company_id' => $companyId,
                    'branch_id' => null,
                    'setting_key' => 'capitalization_life_extension_months',
                    'setting_name' => 'Capitalization Life Extension Threshold',
                    'setting_value' => '12',
                    'description' => 'Minimum life extension (months) to qualify for capitalization',
                    'setting_type' => 'number',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            
            DB::table('maintenance_settings')->insert($defaultSettings);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_settings');
    }
};
