<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grading_scale_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('grading_scale_id');
            $table->integer('min_marks');
            $table->integer('max_marks');
            $table->string('grade', 5);
            $table->string('remark', 50);
            $table->decimal('gpa_points', 3, 2);
            $table->enum('pass_status', ['pass', 'fail'])->default('pass');
            $table->timestamps();

            $table->foreign('grading_scale_id')->references('id')->on('grading_scales')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grading_scale_items');
    }
};
