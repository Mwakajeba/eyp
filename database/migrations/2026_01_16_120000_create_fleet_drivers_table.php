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
        Schema::create('fleet_drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
            
            // Driver identification
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('employee_id')->nullable();
            $table->string('driver_code', 50)->nullable();
            $table->string('full_name', 255);
            
            // License information
            $table->string('license_number', 100);
            $table->string('license_class', 50)->nullable();
            $table->date('license_expiry_date');
            $table->string('license_issuing_authority', 100)->nullable();
            
            // Employment details
            $table->enum('employment_type', ['employee', 'contractor'])->default('employee');
            $table->decimal('daily_allowance_rate', 18, 2)->default(0);
            $table->decimal('overtime_rate', 18, 2)->default(0);
            $table->decimal('salary', 18, 2)->nullable();
            
            // Contact information
            $table->string('phone_number', 50)->nullable();
            $table->string('email', 255)->nullable();
            $table->text('address')->nullable();
            $table->string('emergency_contact_name', 255)->nullable();
            $table->string('emergency_contact_phone', 50)->nullable();
            $table->text('emergency_contact_relationship')->nullable();
            
            // Compliance and training
            $table->json('training_records')->nullable();
            $table->json('compliance_documents')->nullable();
            $table->date('last_training_date')->nullable();
            $table->date('next_training_due_date')->nullable();
            
            // Vehicle assignment
            $table->foreignId('assigned_vehicle_id')->nullable()->constrained('assets')->onDelete('set null');
            $table->date('assignment_start_date')->nullable();
            $table->date('assignment_end_date')->nullable();
            
            // Status and notes
            $table->enum('status', ['active', 'inactive', 'suspended', 'terminated'])->default('active');
            $table->text('notes')->nullable();
            
            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['company_id', 'status']);
            $table->index(['branch_id', 'status']);
            $table->index('license_number');
            $table->index('license_expiry_date');
            $table->index('employee_id');
            $table->unique(['company_id', 'license_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fleet_drivers');
    }
};
