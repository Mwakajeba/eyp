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
        Schema::create('fleet_workflow_approvers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workflow_id');
            $table->unsignedBigInteger('user_id');
            $table->integer('approval_order')->default(1);
            $table->decimal('max_approval_amount', 15, 2)->nullable();
            $table->boolean('can_approve_all')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('workflow_id')->references('id')->on('fleet_approval_workflows')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users');
            $table->unique(['workflow_id', 'user_id']);
            $table->index(['workflow_id', 'approval_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fleet_workflow_approvers');
    }
};
