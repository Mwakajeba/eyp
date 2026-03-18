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
        if (Schema::hasTable('hr_biometric_employee_mappings')) {
            return;
        }

        Schema::create('hr_biometric_employee_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('hr_biometric_devices')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('hr_employees')->onDelete('cascade');
            $table->string('device_user_id', 100)->notNull(); // User ID stored in the device
            $table->string('device_user_name', 200)->nullable(); // Name stored in device
            $table->boolean('is_active')->default(true);
            $table->timestamp('mapped_at')->useCurrent();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['device_id', 'device_user_id']);
            $table->unique(['device_id', 'employee_id']);
            $table->index(['employee_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_biometric_employee_mappings');
    }
};

