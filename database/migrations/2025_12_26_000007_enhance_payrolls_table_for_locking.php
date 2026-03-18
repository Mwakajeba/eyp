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
        Schema::table('payrolls', function (Blueprint $table) {
            if (!Schema::hasColumn('payrolls', 'is_locked')) {
                $table->boolean('is_locked')->default(false)->after('status');
            }
            if (!Schema::hasColumn('payrolls', 'locked_at')) {
                $table->timestamp('locked_at')->nullable()->after('is_locked');
            }
            if (!Schema::hasColumn('payrolls', 'locked_by')) {
                $table->foreignId('locked_by')->nullable()->after('locked_at')->constrained('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('payrolls', 'lock_reason')) {
                $table->text('lock_reason')->nullable()->after('locked_by');
            }
            if (!Schema::hasColumn('payrolls', 'can_be_reversed')) {
                $table->boolean('can_be_reversed')->default(true)->after('lock_reason');
            }
            if (!Schema::hasColumn('payrolls', 'reversed_at')) {
                $table->timestamp('reversed_at')->nullable()->after('can_be_reversed');
            }
            if (!Schema::hasColumn('payrolls', 'reversed_by')) {
                $table->foreignId('reversed_by')->nullable()->after('reversed_at')->constrained('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('payrolls', 'reversal_reason')) {
                $table->text('reversal_reason')->nullable()->after('reversed_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropForeign(['locked_by']);
            $table->dropForeign(['reversed_by']);
            $table->dropColumn([
                'is_locked',
                'locked_at',
                'locked_by',
                'lock_reason',
                'can_be_reversed',
                'reversed_at',
                'reversed_by',
                'reversal_reason'
            ]);
        });
    }
};

