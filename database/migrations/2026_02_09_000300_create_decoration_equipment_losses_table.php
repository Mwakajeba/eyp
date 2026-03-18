<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('decoration_equipment_losses', function (Blueprint $table) {
            $table->id();
            $table->string('loss_number')->unique();
            $table->unsignedBigInteger('decoration_job_id')->nullable();
            $table->unsignedBigInteger('equipment_id');
            $table->unsignedBigInteger('responsible_employee_id')->nullable();
            $table->enum('loss_type', ['business', 'employee'])->default('business');
            $table->integer('quantity_lost');
            $table->date('loss_date')->nullable();
            $table->text('reason')->nullable();
            $table->enum('status', ['draft', 'confirmed', 'cancelled'])->default('draft');

            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('decoration_job_id')->references('id')->on('decoration_jobs');
            $table->foreign('equipment_id')->references('id')->on('equipment');
            $table->foreign('responsible_employee_id')->references('id')->on('users');
            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('decoration_equipment_losses');
    }
};

