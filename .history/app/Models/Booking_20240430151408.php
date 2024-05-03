<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class Booking extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'user_id', 'room_id', 'check_in_date', 'check_out_date', 'num_adults', 'num_children','payment_session_id', 'payment_status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
    // Booking model

    public function services() {
        return $this->belongsToMany(Service::class, 'booking_service')
                    ->withPivot('quantity', 'total_price')
                    ->withTimestamps(); // Optional: To include timestamps in the pivot table
    }


    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }
    
}

