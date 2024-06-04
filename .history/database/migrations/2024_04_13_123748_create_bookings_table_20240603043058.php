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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
    
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('room_id');
            $table->date('check_in_date');
            $table->date('check_out_date');
            $table->integer('num_adults');
            $table->integer('num_children');
            $table->string('payment_session_id')->nullable();
            $table->enum('payment_status', ['fully_paid', 'Pre_payment', 'not_paid', 'cancel'])->default('not_paid');
            $table->enum('payment_method')->nullable();
            $table->timestamps();
    
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('room_id')->references('id')->on('rooms')->onDelete('cascade');
        });
        Schema::table('bookings', function (Blueprint $table) {
            $table->timestamp('session_expiry')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
