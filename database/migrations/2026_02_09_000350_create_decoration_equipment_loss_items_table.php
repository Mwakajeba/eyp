<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('decoration_equipment_loss_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loss_id');
            $table->unsignedBigInteger('equipment_id');
            $table->integer('quantity_lost');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('loss_id')->references('id')->on('decoration_equipment_losses')->onDelete('cascade');
            $table->foreign('equipment_id')->references('id')->on('equipment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('decoration_equipment_loss_items');
    }
};

