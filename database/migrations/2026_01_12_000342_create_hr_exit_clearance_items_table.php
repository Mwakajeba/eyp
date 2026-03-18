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
        Schema::create('hr_exit_clearance_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exit_id')->constrained('hr_exits')->onDelete('cascade');
            $table->string('clearance_item', 200); // 'laptop_returned', 'id_returned', 'access_removed', 'debt_cleared', etc.
            $table->string('department', 100)->nullable(); // 'IT', 'Finance', 'HR', etc.
            $table->string('status', 50)->default('pending'); // 'pending', 'completed', 'waived'
            $table->foreignId('completed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->integer('sequence_order')->default(0);
            $table->timestamps();

            $table->index(['exit_id', 'status']);
            $table->index(['exit_id', 'sequence_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_exit_clearance_items');
    }
};
