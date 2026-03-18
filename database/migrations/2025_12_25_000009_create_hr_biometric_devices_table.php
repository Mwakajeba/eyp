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
        if (Schema::hasTable('hr_biometric_devices')) {
            return;
        }

        Schema::create('hr_biometric_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
            $table->string('device_code', 50)->notNull();
            $table->string('device_name', 200)->notNull();
            $table->string('device_type', 50)->default('fingerprint'); // 'fingerprint', 'face', 'card', 'palm'
            $table->string('device_model', 100)->nullable();
            $table->string('serial_number', 100)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->integer('port')->nullable();
            $table->string('api_key', 255)->nullable(); // For API authentication
            $table->string('api_secret', 255)->nullable();
            $table->string('connection_type', 50)->default('api'); // 'api', 'tcp', 'udp', 'file_import'
            $table->json('connection_config')->nullable(); // Additional connection settings
            $table->string('timezone', 50)->default('Africa/Dar_es_Salaam');
            $table->boolean('auto_sync')->default(true);
            $table->integer('sync_interval_minutes')->default(15); // How often to sync
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamp('last_successful_sync_at')->nullable();
            $table->integer('sync_failure_count')->default(0);
            $table->text('last_error')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'device_code']);
            $table->index(['company_id', 'branch_id']);
            $table->index('is_active');
            $table->index('last_sync_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_biometric_devices');
    }
};

