<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_openings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('asset_id')->nullable();
            $table->string('asset_code')->nullable();
            $table->string('asset_name');
            $table->unsignedBigInteger('asset_category_id')->nullable();
            $table->string('tax_pool_class')->nullable();
            $table->date('opening_date');
            $table->decimal('opening_cost', 18, 2)->default(0);
            $table->decimal('opening_accum_depr', 18, 2)->default(0);
            $table->decimal('opening_nbv', 18, 2)->default(0);
            $table->text('notes')->nullable();
            $table->boolean('gl_post')->default(false);
            $table->boolean('gl_posted')->default(false);
            $table->unsignedBigInteger('gl_journal_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_openings');
    }
};


