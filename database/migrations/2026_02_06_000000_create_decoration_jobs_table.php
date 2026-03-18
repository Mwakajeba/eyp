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
        Schema::create('decoration_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('job_number')->unique();
            $table->unsignedBigInteger('customer_id');
            $table->date('event_date')->nullable();
            $table->string('event_location')->nullable();
            $table->string('event_theme')->nullable();
            $table->string('package_name')->nullable();
            $table->text('service_description')->nullable();
            $table->decimal('agreed_price', 18, 2)->default(0);
            $table->enum('status', [
                'draft',
                'planned',
                'confirmed',
                'in_progress',
                'completed',
                'cancelled',
            ])->default('draft');
            $table->text('notes')->nullable();

            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('customer_id')->references('id')->on('customers');
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
        Schema::dropIfExists('decoration_jobs');
    }
};
