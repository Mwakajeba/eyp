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
        Schema::create('rental_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_id')->constrained('rental_returns')->onDelete('cascade');
            $table->foreignId('dispatch_item_id')->constrained('rental_dispatch_items')->onDelete('cascade');
            $table->foreignId('equipment_id')->constrained('equipment')->onDelete('cascade');
            $table->integer('quantity_returned');
            $table->enum('condition', ['good', 'damaged', 'lost'])->default('good');
            $table->text('condition_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rental_return_items');
    }
};
