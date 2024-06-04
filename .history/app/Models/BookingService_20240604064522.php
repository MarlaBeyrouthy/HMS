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

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Define the relationship between BookingService and Service.
     */
    public function service()
    {
        return $this->belongsTo(Service::class);
    }
    
}

