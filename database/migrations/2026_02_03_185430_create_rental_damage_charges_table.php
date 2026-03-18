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
        Schema::create('rental_damage_charges', function (Blueprint $table) {
            $table->id();
            $table->string('charge_number')->unique();
            $table->foreignId('return_id')->constrained('rental_returns')->onDelete('cascade');
            $table->foreignId('dispatch_id')->constrained('rental_dispatches')->onDelete('cascade');
            $table->foreignId('contract_id')->constrained('rental_contracts')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->date('charge_date');
            $table->decimal('total_damage_charges', 15, 2)->default(0);
            $table->decimal('total_loss_charges', 15, 2)->default(0);
            $table->decimal('total_charges', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'invoiced', 'cancelled'])->default('draft');
            $table->unsignedBigInteger('invoice_id')->nullable(); // Will be constrained after rental_invoices table is created
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('cascade');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rental_damage_charges');
    }
};
