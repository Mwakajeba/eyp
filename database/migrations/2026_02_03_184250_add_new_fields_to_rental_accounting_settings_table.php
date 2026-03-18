<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add columns only if they don't exist
        if (!Schema::hasColumn('rental_accounting_settings', 'rental_equipment_account_id')) {
            Schema::table('rental_accounting_settings', function (Blueprint $table) {
                $table->unsignedBigInteger('rental_equipment_account_id')->nullable()->after('expenses_account_id');
            });
        }
        
        if (!Schema::hasColumn('rental_accounting_settings', 'equipment_under_repair_account_id')) {
            Schema::table('rental_accounting_settings', function (Blueprint $table) {
                $table->unsignedBigInteger('equipment_under_repair_account_id')->nullable()->after('rental_equipment_account_id');
            });
        }
        
        if (!Schema::hasColumn('rental_accounting_settings', 'accounts_receivable_account_id')) {
            Schema::table('rental_accounting_settings', function (Blueprint $table) {
                $table->unsignedBigInteger('accounts_receivable_account_id')->nullable()->after('equipment_under_repair_account_id');
            });
        }
        
        if (!Schema::hasColumn('rental_accounting_settings', 'damage_recovery_income_account_id')) {
            Schema::table('rental_accounting_settings', function (Blueprint $table) {
                $table->unsignedBigInteger('damage_recovery_income_account_id')->nullable()->after('service_income_account_id');
            });
        }
        
        if (!Schema::hasColumn('rental_accounting_settings', 'repair_maintenance_expense_account_id')) {
            Schema::table('rental_accounting_settings', function (Blueprint $table) {
                $table->unsignedBigInteger('repair_maintenance_expense_account_id')->nullable()->after('expenses_account_id');
            });
        }
        
        if (!Schema::hasColumn('rental_accounting_settings', 'loss_on_equipment_account_id')) {
            Schema::table('rental_accounting_settings', function (Blueprint $table) {
                $table->unsignedBigInteger('loss_on_equipment_account_id')->nullable()->after('repair_maintenance_expense_account_id');
            });
        }

        // Add foreign keys with shorter constraint names (only if they don't exist)
        try {
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'rental_accounting_settings' 
                AND CONSTRAINT_NAME LIKE 'rental_acct_%'
            ");
            $existingKeys = array_column($foreignKeys, 'CONSTRAINT_NAME');
        } catch (\Exception $e) {
            $existingKeys = [];
        }

        // Add foreign keys one by one
        if (!in_array('rental_acct_rental_equip_fk', $existingKeys) && Schema::hasColumn('rental_accounting_settings', 'rental_equipment_account_id')) {
            try {
                Schema::table('rental_accounting_settings', function (Blueprint $table) {
                    $table->foreign('rental_equipment_account_id', 'rental_acct_rental_equip_fk')
                        ->references('id')->on('chart_accounts')->onDelete('set null');
                });
            } catch (\Exception $e) {
                // Foreign key might already exist with different name
            }
        }
        
        if (!in_array('rental_acct_equip_repair_fk', $existingKeys) && Schema::hasColumn('rental_accounting_settings', 'equipment_under_repair_account_id')) {
            try {
                Schema::table('rental_accounting_settings', function (Blueprint $table) {
                    $table->foreign('equipment_under_repair_account_id', 'rental_acct_equip_repair_fk')
                        ->references('id')->on('chart_accounts')->onDelete('set null');
                });
            } catch (\Exception $e) {
                // Foreign key might already exist with different name
            }
        }
        
        if (!in_array('rental_acct_ar_fk', $existingKeys) && Schema::hasColumn('rental_accounting_settings', 'accounts_receivable_account_id')) {
            try {
                Schema::table('rental_accounting_settings', function (Blueprint $table) {
                    $table->foreign('accounts_receivable_account_id', 'rental_acct_ar_fk')
                        ->references('id')->on('chart_accounts')->onDelete('set null');
                });
            } catch (\Exception $e) {
                // Foreign key might already exist with different name
            }
        }
        
        if (!in_array('rental_acct_damage_income_fk', $existingKeys) && Schema::hasColumn('rental_accounting_settings', 'damage_recovery_income_account_id')) {
            try {
                Schema::table('rental_accounting_settings', function (Blueprint $table) {
                    $table->foreign('damage_recovery_income_account_id', 'rental_acct_damage_income_fk')
                        ->references('id')->on('chart_accounts')->onDelete('set null');
                });
            } catch (\Exception $e) {
                // Foreign key might already exist with different name
            }
        }
        
        if (!in_array('rental_acct_repair_exp_fk', $existingKeys) && Schema::hasColumn('rental_accounting_settings', 'repair_maintenance_expense_account_id')) {
            try {
                Schema::table('rental_accounting_settings', function (Blueprint $table) {
                    $table->foreign('repair_maintenance_expense_account_id', 'rental_acct_repair_exp_fk')
                        ->references('id')->on('chart_accounts')->onDelete('set null');
                });
            } catch (\Exception $e) {
                // Foreign key might already exist with different name
            }
        }
        
        if (!in_array('rental_acct_loss_equip_fk', $existingKeys) && Schema::hasColumn('rental_accounting_settings', 'loss_on_equipment_account_id')) {
            try {
                Schema::table('rental_accounting_settings', function (Blueprint $table) {
                    $table->foreign('loss_on_equipment_account_id', 'rental_acct_loss_equip_fk')
                        ->references('id')->on('chart_accounts')->onDelete('set null');
                });
            } catch (\Exception $e) {
                // Foreign key might already exist with different name
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rental_accounting_settings', function (Blueprint $table) {
            $table->dropForeign('rental_acct_rental_equip_fk');
            $table->dropForeign('rental_acct_equip_repair_fk');
            $table->dropForeign('rental_acct_ar_fk');
            $table->dropForeign('rental_acct_damage_income_fk');
            $table->dropForeign('rental_acct_repair_exp_fk');
            $table->dropForeign('rental_acct_loss_equip_fk');
            $table->dropColumn([
                'rental_equipment_account_id',
                'equipment_under_repair_account_id',
                'accounts_receivable_account_id',
                'damage_recovery_income_account_id',
                'repair_maintenance_expense_account_id',
                'loss_on_equipment_account_id'
            ]);
        });
    }
};
