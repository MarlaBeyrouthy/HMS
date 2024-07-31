<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class BookingService extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'booking_service';

    protected $fillable = ['booking_id', 'service_id', 'quantity', 'total_price'];

}

