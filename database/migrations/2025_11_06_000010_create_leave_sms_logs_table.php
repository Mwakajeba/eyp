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
        Schema::create('leave_sms_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_request_id')->constrained('leave_requests')->cascadeOnDelete();
            $table->foreignId('recipient_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->string('phone_number');
            $table->text('message');
            $table->enum('type', [
                'request_submitted',
                'request_approved',
                'request_rejected',
                'request_returned',
                'pending_approval',
                'reliever_assigned'
            ]);
            $table->enum('status', ['queued', 'sent', 'failed'])->default('queued');
            $table->text('error_message')->nullable();
            $table->json('response')->nullable();
            $table->timestamps();

            $table->index(['leave_request_id', 'type']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_sms_logs');
    }
};

