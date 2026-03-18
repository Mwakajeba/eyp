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
        Schema::create('rental_damage_charge_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('damage_charge_id')->constrained('rental_damage_charges')->onDelete('cascade');
            $table->foreignId('return_item_id')->nullable()->constrained('rental_return_items')->onDelete('set null');
            $table->foreignId('equipment_id')->constrained('equipment')->onDelete('cascade');
            $table->enum('charge_type', ['damage', 'loss'])->default('damage');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_charge', 15, 2)->default(0);
            $table->decimal('total_charge', 15, 2)->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rental_damage_charge_items');
    }
};
