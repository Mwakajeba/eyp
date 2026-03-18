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
        Schema::create('maintenance_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('request_number')->unique();
            $table->unsignedBigInteger('asset_id');
            $table->unsignedBigInteger('maintenance_type_id');
            $table->enum('trigger_type', ['preventive', 'corrective', 'planned_improvement'])->default('corrective');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->text('description');
            $table->text('issue_details')->nullable();
            $table->date('requested_date');
            $table->date('preferred_start_date')->nullable();
            $table->unsignedBigInteger('requested_by'); // User who initiated
            $table->unsignedBigInteger('custodian_user_id')->nullable(); // Asset custodian
            $table->unsignedBigInteger('department_id')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'converted_to_wo', 'cancelled'])->default('pending');
            $table->unsignedBigInteger('supervisor_approved_by')->nullable();
            $table->timestamp('supervisor_approved_at')->nullable();
            $table->text('supervisor_notes')->nullable();
            $table->unsignedBigInteger('work_order_id')->nullable(); // If converted to WO
            $table->json('attachments')->nullable(); // Images, reports, etc.
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
            $table->foreign('asset_id')->references('id')->on('assets')->onDelete('cascade');
            $table->foreign('maintenance_type_id')->references('id')->on('maintenance_types')->onDelete('restrict');
            $table->foreign('requested_by')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('custodian_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('supervisor_approved_by')->references('id')->on('users')->onDelete('set null');
            // Note: work_order_id foreign key will be added in a separate migration after work_orders table is created
            $table->index(['company_id', 'branch_id', 'status']);
            $table->index(['asset_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_requests');
    }
};
