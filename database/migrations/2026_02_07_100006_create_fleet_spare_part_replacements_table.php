<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleet_spare_part_replacements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
            $table->foreignId('vehicle_id')->constrained('assets')->onDelete('cascade');
            $table->foreignId('spare_part_category_id')->constrained('fleet_spare_part_categories')->onDelete('restrict');
            $table->date('replaced_at');
            $table->decimal('odometer_at_replacement', 12, 2)->nullable();
            $table->decimal('cost', 18, 2)->nullable();
            $table->text('reason')->nullable();
            $table->json('attachments')->nullable()->comment('Photo or garage report');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'vehicle_id']);
            $table->index(['spare_part_category_id', 'replaced_at'], 'fsp_replacements_cat_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_spare_part_replacements');
    }
};
