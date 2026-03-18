<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleet_tyres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
            $table->string('tyre_serial', 100)->nullable()->comment('System-generated Tyre ID/code');
            $table->string('dot_number', 100)->nullable()->comment('DOT number from tyre sidewall');
            $table->string('brand', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->string('tyre_size', 50)->nullable();
            $table->string('supplier', 255)->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_cost', 18, 2)->nullable();
            $table->enum('warranty_type', ['distance', 'time'])->nullable()->comment('distance=km, time=months');
            $table->decimal('warranty_limit_value', 18, 2)->nullable()->comment('km or months');
            $table->decimal('expected_lifespan_km', 12, 2)->nullable();
            $table->enum('status', ['new', 'in_use', 'removed', 'under_warranty_claim', 'scrapped'])->default('new');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['company_id', 'status']);
            $table->unique(['company_id', 'tyre_serial']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_tyres');
    }
};
