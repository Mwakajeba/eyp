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
        if (Schema::hasTable('hr_biometric_logs')) {
            return;
        }

        Schema::create('hr_biometric_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('hr_biometric_devices')->onDelete('cascade');
            $table->string('device_user_id', 100)->nullable(); // User ID from the device
            $table->foreignId('employee_id')->nullable()->constrained('hr_employees')->onDelete('set null');
            $table->timestamp('punch_time')->notNull(); // Clock in/out time from device
            $table->string('punch_type', 50)->default('check_in'); // 'check_in', 'check_out', 'break_in', 'break_out'
            $table->string('punch_mode', 50)->nullable(); // 'fingerprint', 'face', 'card', 'palm', 'password'
            $table->string('status', 50)->default('pending'); // 'pending', 'processed', 'failed', 'duplicate'
            $table->foreignId('attendance_id')->nullable()->constrained('hr_attendance')->onDelete('set null');
            $table->text('raw_data')->nullable(); // JSON raw data from device
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['device_id', 'punch_time']);
            $table->index(['employee_id', 'punch_time']);
            $table->index('status');
            $table->index('punch_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_biometric_logs');
    }
};

