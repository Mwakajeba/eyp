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
        Schema::table('hfs_requests', function (Blueprint $table) {
            $table->boolean('is_overdue')->default(false)->after('extension_approved_at');
            $table->dateTime('overdue_notified_at')->nullable()->after('is_overdue');
            $table->dateTime('last_alert_sent_at')->nullable()->after('overdue_notified_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hfs_requests', function (Blueprint $table) {
            $table->dropColumn(['is_overdue', 'overdue_notified_at', 'last_alert_sent_at']);
        });
    }
};
