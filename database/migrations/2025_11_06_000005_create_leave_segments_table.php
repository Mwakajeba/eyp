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
        Schema::create('leave_segments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_request_id')->constrained('leave_requests')->cascadeOnDelete();
            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();
            $table->enum('granularity', ['full_day', 'half_day', 'hourly'])->default('full_day');
            $table->decimal('days_equivalent', 8, 2); // computed by engine for balance
            $table->json('calculation')->nullable(); // breakdown: weekends/holidays skipped or counted
            $table->timestamps();

            $table->index(['leave_request_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_segments');
    }
};

