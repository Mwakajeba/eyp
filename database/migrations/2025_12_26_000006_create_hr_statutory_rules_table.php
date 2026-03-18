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
        if (Schema::hasTable('hr_statutory_rules')) {
            return;
        }

        Schema::create('hr_statutory_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('rule_type', 50)->notNull(); // 'paye', 'nhif', 'pension', 'wcf', 'sdl', 'heslb'
            $table->string('rule_name', 200)->notNull();
            $table->text('description')->nullable();
            
            // PAYE specific fields
            $table->json('paye_brackets')->nullable(); // Progressive tax brackets
            $table->decimal('paye_tax_relief', 15, 2)->nullable(); // Tax relief amount
            
            // NHIF specific fields
            $table->decimal('nhif_employee_percent', 5, 2)->nullable();
            $table->decimal('nhif_employer_percent', 5, 2)->nullable();
            $table->decimal('nhif_ceiling', 15, 2)->nullable(); // Maximum contribution
            
            // Pension specific fields
            $table->decimal('pension_employee_percent', 5, 2)->nullable();
            $table->decimal('pension_employer_percent', 5, 2)->nullable();
            $table->decimal('pension_ceiling', 15, 2)->nullable();
            $table->string('pension_scheme_type', 50)->nullable(); // 'nssf', 'psssf', 'other'
            
            // WCF specific fields
            $table->decimal('wcf_employer_percent', 5, 2)->nullable();
            $table->string('industry_type', 100)->nullable();
            
            // SDL specific fields
            $table->decimal('sdl_employer_percent', 5, 2)->nullable();
            $table->decimal('sdl_threshold', 15, 2)->nullable(); // Minimum threshold
            
            // HESLB specific fields
            $table->decimal('heslb_percent', 5, 2)->nullable();
            $table->decimal('heslb_ceiling', 15, 2)->nullable();
            
            // Common fields
            $table->date('effective_from')->notNull();
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'rule_type', 'is_active']);
            $table->index('effective_from');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_statutory_rules');
    }
};

