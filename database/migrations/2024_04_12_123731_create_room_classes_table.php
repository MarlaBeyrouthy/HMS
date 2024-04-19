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
        Schema::create('room_classes', function (Blueprint $table) {
            $table->id();
            $table->enum('class_name', ['Single Room', 'Double Room', 'Triple Room', 'Quad Room']);
            $table->float('base_price');
            $table->string('bed_type');
            $table->integer('number_of_beds');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_classes');
    }
};
