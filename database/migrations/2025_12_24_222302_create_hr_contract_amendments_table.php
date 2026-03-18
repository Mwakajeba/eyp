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
        if (Schema::hasTable('hr_contract_amendments')) {
            return;
        }

        // Ensure hr_contracts table exists first
        if (!Schema::hasTable('hr_contracts')) {
            throw new \Exception('hr_contracts table must exist before creating hr_contract_amendments. Please run the contracts migration first.');
        }

        Schema::create('hr_contract_amendments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('hr_contracts')->onDelete('cascade');
            $table->string('amendment_type', 50)->nullable(); // 'salary_change', 'role_change', 'extension'
            $table->date('effective_date')->notNull();
            $table->json('old_value')->nullable();
            $table->json('new_value')->nullable();
            $table->text('reason')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['contract_id', 'effective_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_contract_amendments');
    }
};
