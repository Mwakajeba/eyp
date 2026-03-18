<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intangible_asset_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->string('code')->nullable();
            $table->enum('type', ['purchased', 'internally_developed', 'goodwill', 'indefinite_life'])->default('purchased');
            $table->boolean('is_goodwill')->default(false);
            $table->boolean('is_indefinite_life')->default(false);
            $table->unsignedBigInteger('cost_account_id')->nullable();
            $table->unsignedBigInteger('accumulated_amortisation_account_id')->nullable();
            $table->unsignedBigInteger('accumulated_impairment_account_id')->nullable();
            $table->unsignedBigInteger('amortisation_expense_account_id')->nullable();
            $table->unsignedBigInteger('impairment_loss_account_id')->nullable();
            $table->unsignedBigInteger('disposal_gain_loss_account_id')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('intangible_assets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('code')->unique();
            $table->string('name');
            $table->enum('source_type', ['purchased', 'internally_developed', 'goodwill', 'other'])->default('purchased');
            $table->date('acquisition_date')->nullable();
            $table->decimal('cost', 18, 2)->default(0);
            $table->decimal('accumulated_amortisation', 18, 2)->default(0);
            $table->decimal('accumulated_impairment', 18, 2)->default(0);
            $table->decimal('nbv', 18, 2)->default(0);
            $table->integer('useful_life_months')->nullable();
            $table->boolean('is_indefinite_life')->default(false);
            $table->boolean('is_goodwill')->default(false);
            $table->enum('status', ['active', 'fully_amortised', 'impaired', 'disposed'])->default('active');
            $table->text('description')->nullable();
            $table->json('recognition_checks')->nullable();
            $table->unsignedBigInteger('initial_journal_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('intangible_cost_components', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('intangible_asset_id');
            $table->unsignedBigInteger('company_id');
            $table->date('date')->nullable();
            $table->string('type')->nullable();
            $table->string('description')->nullable();
            $table->decimal('amount', 18, 2);
            $table->unsignedBigInteger('source_document_id')->nullable();
            $table->string('source_document_type')->nullable();
            $table->timestamps();
        });

        Schema::create('intangible_amortisations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('intangible_asset_id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->date('amortisation_date');
            $table->decimal('amount', 18, 2);
            $table->decimal('accumulated_amortisation_after', 18, 2);
            $table->decimal('nbv_after', 18, 2);
            $table->unsignedBigInteger('journal_id')->nullable();
            $table->boolean('gl_posted')->default(false);
            $table->timestamps();
        });

        Schema::create('intangible_impairments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('intangible_asset_id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->date('impairment_date');
            $table->decimal('carrying_amount_before', 18, 2);
            $table->decimal('recoverable_amount', 18, 2);
            $table->decimal('impairment_loss', 18, 2);
            $table->enum('method', ['value_in_use', 'fair_value_less_costs'])->nullable();
            $table->text('assumptions')->nullable();
            $table->boolean('is_reversal')->default(false);
            $table->unsignedBigInteger('reversed_impairment_id')->nullable();
            $table->string('status')->default('draft');
            $table->unsignedBigInteger('journal_id')->nullable();
            $table->boolean('gl_posted')->default(false);
            $table->timestamps();
        });

        Schema::create('intangible_disposals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('intangible_asset_id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->date('disposal_date');
            $table->decimal('proceeds', 18, 2)->default(0);
            $table->decimal('nbv_at_disposal', 18, 2)->default(0);
            $table->decimal('gain_loss', 18, 2)->default(0);
            $table->string('status')->default('draft');
            $table->unsignedBigInteger('journal_id')->nullable();
            $table->boolean('gl_posted')->default(false);
            $table->text('reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intangible_disposals');
        Schema::dropIfExists('intangible_impairments');
        Schema::dropIfExists('intangible_amortisations');
        Schema::dropIfExists('intangible_cost_components');
        Schema::dropIfExists('intangible_assets');
        Schema::dropIfExists('intangible_asset_categories');
    }
};


