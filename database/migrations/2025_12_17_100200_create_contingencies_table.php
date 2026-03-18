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
        Schema::create('contingencies', function (Blueprint $table) {
            $table->id();

            // Document number
            $table->string('contingency_number')->unique();

            // Type: contingent liability or contingent asset
            $table->enum('contingency_type', ['liability', 'asset']);

            $table->string('title');
            $table->text('description');

            // Link to related provision if any
            $table->foreignId('provision_id')
                ->nullable()
                ->constrained('provisions')
                ->onDelete('set null');

            // Probability & amount estimation (disclosure only)
            $table->enum('probability', ['remote', 'possible', 'probable', 'virtually_certain'])->default('possible');
            $table->decimal('probability_percent', 5, 2)->nullable();

            $table->string('currency_code', 3)->default('TZS');
            $table->decimal('fx_rate_at_creation', 15, 6)->default(1);
            $table->decimal('expected_amount', 20, 2)->nullable(); // For disclosure; NO posting

            // Status & resolution
            $table->enum('status', ['open', 'resolved', 'cancelled'])->default('open');
            $table->enum('resolution_outcome', ['no_outflow', 'outflow', 'inflow', 'other'])
                ->nullable();
            $table->date('resolution_date')->nullable();
            $table->text('resolution_notes')->nullable();

            // Company / branch context
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');

            // Audit
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['company_id', 'status']);
            $table->index(['contingency_type', 'probability']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contingencies');
    }
};


