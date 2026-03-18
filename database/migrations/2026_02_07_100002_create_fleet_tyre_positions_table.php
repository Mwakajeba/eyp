<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleet_tyre_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('position_code', 50)->nullable()->comment('e.g. FL, FR, R1LI');
            $table->string('position_name', 100)->comment('e.g. Front Left, Rear Axle 1 Left Inner');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['company_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_tyre_positions');
    }
};
