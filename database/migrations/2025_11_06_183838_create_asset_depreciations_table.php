<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_depreciations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('asset_id')->nullable();
            $table->unsignedBigInteger('asset_opening_id')->nullable(); // Link to opening balance if type is opening_balance
            $table->enum('type', ['opening_balance', 'depreciation', 'adjustment', 'disposal'])->default('depreciation');
            $table->date('depreciation_date');
            $table->decimal('depreciation_amount', 18, 2)->default(0);
            $table->decimal('accumulated_depreciation', 18, 2)->default(0); // Total accumulated after this entry
            $table->decimal('book_value_before', 18, 2)->default(0); // Book value before this depreciation
            $table->decimal('book_value_after', 18, 2)->default(0); // Book value after this depreciation
            $table->string('description')->nullable();
            $table->unsignedBigInteger('gl_transaction_id')->nullable(); // Link to GL transaction if posted
            $table->boolean('gl_posted')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'branch_id']);
            $table->index(['asset_id', 'depreciation_date']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_depreciations');
    }
};
