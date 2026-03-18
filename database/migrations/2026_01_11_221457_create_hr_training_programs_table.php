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
        Schema::create('hr_training_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('program_code', 50);
            $table->string('program_name', 200);
            $table->string('provider', 200)->nullable(); // 'internal', 'external'
            $table->decimal('cost', 15, 2)->nullable();
            $table->integer('duration_days')->nullable();
            $table->string('funding_source', 50)->nullable(); // 'sdl', 'internal', 'donor'
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'program_code']);
            $table->index(['company_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_training_programs');
    }
};
