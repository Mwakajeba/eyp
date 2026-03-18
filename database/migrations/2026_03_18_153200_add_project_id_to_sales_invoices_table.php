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
        if (! Schema::hasColumn('sales_invoices', 'project_id')) {
            Schema::table('sales_invoices', function (Blueprint $table) {
                $table->foreignId('project_id')
                    ->nullable()
                    ->after('customer_id')
                    ->constrained('projects')
                    ->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('sales_invoices', 'project_id')) {
            Schema::table('sales_invoices', function (Blueprint $table) {
                $table->dropConstrainedForeignId('project_id');
            });
        }
    }
};
