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
        Schema::table('loans', function (Blueprint $table) {
            // IFRS 9 fields
            $table->decimal('initial_amortised_cost', 15, 2)->nullable()->after('principal_amount'); // Cash received - fees
            $table->decimal('current_amortised_cost', 15, 2)->nullable()->after('initial_amortised_cost'); // Current carrying amount
            $table->boolean('eir_locked')->default(false)->after('effective_interest_rate'); // Lock EIR after approval
            $table->date('eir_locked_at')->nullable()->after('eir_locked');
            $table->unsignedBigInteger('eir_locked_by')->nullable()->after('eir_locked_at');
            
            // Transaction costs breakdown
            $table->decimal('capitalized_fees', 15, 2)->default(0)->after('fees_amount');
            $table->decimal('directly_attributable_costs', 15, 2)->default(0)->after('capitalized_fees');
            
            // Foreign key for EIR locked by
            $table->foreign('eir_locked_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropForeign(['eir_locked_by']);
            $table->dropColumn([
                'initial_amortised_cost',
                'current_amortised_cost',
                'eir_locked',
                'eir_locked_at',
                'eir_locked_by',
                'capitalized_fees',
                'directly_attributable_costs',
            ]);
        });
    }
};

