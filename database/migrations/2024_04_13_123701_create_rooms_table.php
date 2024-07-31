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
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->enum('floor',['GF','1F' ,'2F','HP']);
            $table->enum('status',['available','booked' , 'maintenance']);
            $table->string('room_number');
            $table->unsignedBigInteger('room_class_id');
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->string('view')->nullable(); 
            $table->string('photo')->nullable();
            $table->foreign('room_class_id')->references('id')->on('room_classes')->onDelete('cascade');
            $table->timestamps();
        });
    }

  
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
