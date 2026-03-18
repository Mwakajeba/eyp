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
        Schema::create('hr_qualification_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qualification_id')->constrained('hr_qualifications')->onDelete('cascade');
            $table->string('document_name', 200);
            $table->string('document_type', 100)->nullable(); // e.g., 'transcript', 'certificate', 'diploma', 'degree_certificate'
            $table->boolean('is_required')->default(true);
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('qualification_id');
            $table->index('is_required');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_qualification_documents');
    }
};
