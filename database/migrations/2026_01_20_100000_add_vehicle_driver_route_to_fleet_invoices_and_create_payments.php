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
        Schema::table('fleet_invoices', function (Blueprint $table) {
            $table->foreignId('vehicle_id')->nullable()->after('trip_id')->constrained('assets')->onDelete('set null');
            $table->foreignId('driver_id')->nullable()->after('vehicle_id')->constrained('fleet_drivers')->onDelete('set null');
            $table->foreignId('route_id')->nullable()->after('driver_id')->constrained('fleet_routes')->onDelete('set null');
        });

        Schema::create('fleet_invoice_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fleet_invoice_id')->constrained('fleet_invoices')->onDelete('cascade');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
            $table->decimal('amount', 18, 2);
            $table->date('payment_date');
            $table->foreignId('bank_account_id')->constrained('bank_accounts')->onDelete('restrict');
            $table->string('reference_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->index(['fleet_invoice_id', 'payment_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fleet_invoice_payments');
        Schema::table('fleet_invoices', function (Blueprint $table) {
            $table->dropForeign(['vehicle_id']);
            $table->dropForeign(['driver_id']);
            $table->dropForeign(['route_id']);
        });
    }
};
