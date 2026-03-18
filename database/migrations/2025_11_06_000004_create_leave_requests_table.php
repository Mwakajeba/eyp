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
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('leave_types');
            $table->string('request_number')->unique();
            $table->enum('status', [
                'draft',
                'pending_manager',
                'pending_hr',
                'approved',
                'taken',
                'cancelled',
                'rejected'
            ])->default('draft');
            $table->text('reason')->nullable();
            $table->foreignId('reliever_id')->nullable()->constrained('hr_employees')->nullOnDelete();
            $table->boolean('requires_doc')->default(false);
            $table->string('policy_version')->nullable();
            $table->json('meta')->nullable(); // requestor_timezone, ip, UA, etc.
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('decision_at')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('hr_employees')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->decimal('total_days', 8, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status']);
            $table->index(['employee_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};

