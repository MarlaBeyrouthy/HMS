<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up()
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id');
            $table->decimal('paid_amount', 8, 2);
            $table->decimal('remaining_amount', 8, 2);
            $table->decimal('total_amount', 8, 2);
            $table->date('invoice_date');
            $table->text('taxes')->nullable();
            $table->json('services')->nullable();
            $table->timestamps();

            $table->foreign('booking_id')->references('id')->on('bookings');
        });
    }

   
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
