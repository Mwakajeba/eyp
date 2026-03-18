<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gl_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('gl_transactions', 'asset_id')) {
                $table->unsignedBigInteger('asset_id')->nullable()->after('supplier_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('gl_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('gl_transactions', 'asset_id')) {
                $table->dropColumn('asset_id');
            }
        });
    }
};


