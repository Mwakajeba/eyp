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
        Schema::create('imprest_activity_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('imprest_request_id')->constrained('imprest_requests')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users');
            $table->string('description');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('imprest_activity_reports');
    }
};
