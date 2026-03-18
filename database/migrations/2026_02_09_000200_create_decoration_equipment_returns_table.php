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
        Schema::create('decoration_equipment_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number')->unique();
            $table->unsignedBigInteger('issue_id');
            $table->unsignedBigInteger('decoration_job_id');
            $table->date('return_date');
            $table->text('notes')->nullable();
            $table->enum('status', [
                'draft',
                'completed',
                'cancelled',
            ])->default('draft');

            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('issue_id')->references('id')->on('decoration_equipment_issues');
            $table->foreign('decoration_job_id')->references('id')->on('decoration_jobs');
            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        Schema::create('decoration_equipment_return_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('return_id');
            $table->unsignedBigInteger('issue_item_id');
            $table->unsignedBigInteger('equipment_id');
            $table->integer('quantity_returned');
            $table->enum('condition', ['good', 'damaged', 'lost']);
            $table->text('condition_notes')->nullable();

            $table->timestamps();

            $table->foreign('return_id')->references('id')->on('decoration_equipment_returns')->onDelete('cascade');
            $table->foreign('issue_item_id')->references('id')->on('decoration_equipment_issue_items');
            $table->foreign('equipment_id')->references('id')->on('equipment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('decoration_equipment_return_items');
        Schema::dropIfExists('decoration_equipment_returns');
    }
};

