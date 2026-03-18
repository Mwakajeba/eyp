<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('hr_employees', function (Blueprint $table) {
            $table->foreignId('biometric_device_id')->nullable()->after('status')->constrained('hr_biometric_devices')->onDelete('set null');
            $table->string('biometric_device_user_id', 100)->nullable()->after('biometric_device_id');
            $table->string('biometric_device_user_name', 200)->nullable()->after('biometric_device_user_id');
            $table->timestamp('biometric_synced_at')->nullable()->after('biometric_device_user_name');

            $table->index('biometric_device_id');
            $table->index('biometric_device_user_id');
        });

        // Migrate existing mapping data to employees table
        if (Schema::hasTable('hr_biometric_employee_mappings')) {
            DB::statement("
                UPDATE hr_employees e
                INNER JOIN hr_biometric_employee_mappings m ON e.id = m.employee_id
                SET
                    e.biometric_device_id = m.device_id,
                    e.biometric_device_user_id = m.device_user_id,
                    e.biometric_device_user_name = m.device_user_name,
                    e.biometric_synced_at = m.last_synced_at
                WHERE m.is_active = 1
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_employees', function (Blueprint $table) {
            $table->dropForeign(['biometric_device_id']);
            $table->dropIndex(['biometric_device_id']);
            $table->dropIndex(['biometric_device_user_id']);
            $table->dropColumn([
                'biometric_device_id',
                'biometric_device_user_id',
                'biometric_device_user_name',
                'biometric_synced_at'
            ]);
        });
    }
};
