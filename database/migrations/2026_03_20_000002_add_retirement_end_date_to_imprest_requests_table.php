<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('imprest_requests', 'retirement_end_date')) {
            Schema::table('imprest_requests', function (Blueprint $table) {
                $table->date('retirement_end_date')->nullable()->after('disbursed_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('imprest_requests', 'retirement_end_date')) {
            Schema::table('imprest_requests', function (Blueprint $table) {
                $table->dropColumn('retirement_end_date');
            });
        }
    }
};
