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
        Schema::create('fleet_compliance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
            
            // Record identification
            $table->string('record_number', 50)->unique();
            $table->enum('compliance_type', ['vehicle_insurance', 'driver_license', 'vehicle_inspection', 'safety_certification', 'registration', 'permit', 'other'])->default('other');
            
            // Entity references
            $table->foreignId('vehicle_id')->nullable()->constrained('assets')->onDelete('cascade');
            $table->foreignId('driver_id')->nullable()->constrained('fleet_drivers')->onDelete('cascade');
            
            // Compliance details
            $table->string('document_number', 100)->nullable(); // Policy number, license number, etc.
            $table->string('issuer_name', 255)->nullable(); // Insurance company, licensing authority, etc.
            $table->date('issue_date')->nullable();
            $table->date('expiry_date');
            $table->date('renewal_reminder_date')->nullable(); // When to send reminder
            
            // Status and compliance
            $table->enum('status', ['active', 'expired', 'pending_renewal', 'renewed', 'cancelled'])->default('active');
            $table->enum('compliance_status', ['compliant', 'non_compliant', 'warning', 'critical'])->default('compliant');
            
            // Financial details (for insurance, etc.)
            $table->decimal('premium_amount', 18, 2)->nullable();
            $table->string('currency', 10)->default('TZS');
            $table->string('payment_frequency', 50)->nullable(); // monthly, quarterly, annual
            
            // Description and notes
            $table->text('description')->nullable();
            $table->text('terms_conditions')->nullable();
            $table->text('notes')->nullable();
            
            // Document attachments
            $table->json('attachments')->nullable(); // Store file paths and metadata
            
            // Renewal tracking
            $table->foreignId('parent_record_id')->nullable()->constrained('fleet_compliance_records')->onDelete('set null'); // For renewal tracking
            $table->boolean('auto_renewal_enabled')->default(false);
            
            // Notification and alerts
            $table->boolean('reminder_sent')->default(false);
            $table->timestamp('last_reminder_sent_at')->nullable();
            
            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['company_id', 'compliance_type', 'status']);
            $table->index(['vehicle_id', 'compliance_type']);
            $table->index(['driver_id', 'compliance_type']);
            $table->index(['expiry_date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fleet_compliance_records');
    }
};
