<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleet_spare_part_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('name', 100)->comment('e.g. Brake pads, Clutch plate, Oil filter');
            $table->string('code', 50)->nullable();
            $table->decimal('expected_lifespan_km', 12, 2)->nullable();
            $table->unsignedInteger('expected_lifespan_months')->nullable();
            $table->decimal('min_replacement_interval_km', 12, 2)->nullable()->comment('Minimum km before replacement allowed');
            $table->unsignedInteger('min_replacement_interval_months')->nullable();
            $table->decimal('standard_cost_min', 18, 2)->nullable();
            $table->decimal('standard_cost_max', 18, 2)->nullable();
            $table->decimal('approval_threshold', 18, 2)->nullable()->comment('Amount above which extra approval needed');
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->index(['company_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_spare_part_categories');
    }
};
