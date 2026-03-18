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
        Schema::create('decoration_equipment_issues', function (Blueprint $table) {
            $table->id();
            $table->string('issue_number')->unique();
            $table->unsignedBigInteger('decoration_job_id');
            $table->unsignedBigInteger('decorator_id')->nullable();
            $table->date('issue_date');
            $table->text('notes')->nullable();
            $table->enum('status', [
                'draft',
                'issued',
                'returned',
                'cancelled',
            ])->default('draft');

            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('decoration_job_id')->references('id')->on('decoration_jobs');
            $table->foreign('decorator_id')->references('id')->on('users');
            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        Schema::create('decoration_equipment_issue_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('issue_id');
            $table->unsignedBigInteger('equipment_id');
            $table->integer('quantity_issued');
            $table->text('remarks')->nullable();

            $table->timestamps();

            $table->foreign('issue_id')->references('id')->on('decoration_equipment_issues')->onDelete('cascade');
            $table->foreign('equipment_id')->references('id')->on('equipment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('decoration_equipment_issue_items');
        Schema::dropIfExists('decoration_equipment_issues');
    }
};

