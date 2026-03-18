<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->string('model')->nullable()->after('name');
            $table->string('manufacturer')->nullable()->after('model');
            $table->date('capitalization_date')->nullable()->after('purchase_date');
            $table->unsignedInteger('warranty_months')->nullable()->after('capitalization_date');
            $table->date('warranty_expiry_date')->nullable()->after('warranty_months');
            $table->string('insurance_policy_no')->nullable()->after('warranty_expiry_date');
            $table->decimal('insured_value', 18, 2)->nullable()->after('insurance_policy_no');
            $table->date('insurance_expiry_date')->nullable()->after('insured_value');
            $table->string('building_reference')->nullable()->after('location');
            $table->decimal('gps_lat', 10, 7)->nullable()->after('building_reference');
            $table->decimal('gps_lng', 10, 7)->nullable()->after('gps_lat');
            $table->unsignedBigInteger('custodian_user_id')->nullable()->after('department_id');
            $table->enum('status', ['active','under_construction','under_repair','disposed','retired'])->default('active')->change();
            $table->decimal('current_nbv', 18, 2)->nullable()->after('salvage_value');
            $table->json('attachments')->nullable()->after('current_nbv');
            $table->string('barcode')->nullable()->after('tag');
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            // Note: cannot easily revert enum change portably
            $table->dropColumn([
                'model','manufacturer','capitalization_date','warranty_months','warranty_expiry_date',
                'insurance_policy_no','insured_value','insurance_expiry_date','building_reference',
                'gps_lat','gps_lng','custodian_user_id','current_nbv','attachments','barcode'
            ]);
        });
    }
};


