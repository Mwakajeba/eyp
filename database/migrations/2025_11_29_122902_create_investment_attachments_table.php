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
        Schema::create('investment_attachments', function (Blueprint $table) {
            $table->id();
            $table->string('attachable_type'); // 'App\Models\Investment\InvestmentProposal' or 'App\Models\Investment\InvestmentMaster'
            $table->unsignedBigInteger('attachable_id');
            
            // File details
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type', 50)->nullable(); // mime type
            $table->unsignedBigInteger('file_size')->nullable(); // in bytes
            $table->string('document_type', 100)->nullable(); // 'proposal_doc', 'approval_doc', 'contract', etc.
            $table->text('description')->nullable();
            
            // Audit fields
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['attachable_type', 'attachable_id']);
            $table->index('document_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investment_attachments');
    }
};
