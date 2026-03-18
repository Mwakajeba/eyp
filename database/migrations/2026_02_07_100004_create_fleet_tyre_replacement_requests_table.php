<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleet_tyre_replacement_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
            $table->foreignId('vehicle_id')->constrained('assets')->onDelete('cascade');
            $table->foreignId('tyre_position_id')->constrained('fleet_tyre_positions')->onDelete('restrict');
            $table->foreignId('current_tyre_id')->nullable()->constrained('fleet_tyres')->onDelete('set null');
            $table->foreignId('current_installation_id')->nullable()->constrained('fleet_tyre_installations')->onDelete('set null');
            $table->enum('reason', ['worn_out', 'burst', 'side_cut', 'other'])->default('worn_out');
            $table->decimal('mileage_at_request', 12, 2)->nullable();
            $table->decimal('tyre_mileage_used', 12, 2)->nullable()->comment('Mileage on this tyre since installation');
            $table->json('photos')->nullable()->comment('Paths to tread, sidewall, DOT photos');
            $table->string('risk_score', 20)->nullable()->comment('low, medium, high');
            $table->enum('status', ['pending', 'approved', 'rejected', 'inspected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'status']);
            $table->index(['vehicle_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_tyre_replacement_requests');
    }
};
