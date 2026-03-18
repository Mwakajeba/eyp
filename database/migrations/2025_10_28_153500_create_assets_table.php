<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('asset_category_id');
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_cost', 18, 2)->default(0);
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->string('location')->nullable();
            $table->string('serial_number')->nullable();
            $table->decimal('salvage_value', 18, 2)->default(0);
            $table->unsignedBigInteger('department_id')->nullable();
            $table->enum('status', ['active','disposed','impaired'])->default('active');
            $table->string('tag')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};


