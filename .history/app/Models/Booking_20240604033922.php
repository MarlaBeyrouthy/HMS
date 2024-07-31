<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class Booking extends Model
{
use HasApiTokens, HasFactory, Notifiable;
php

protected $fillable = [
    'user_id',
    'room_id',
    'check_in_date',
    'check_out_date',
    'num_adults',
    'num_children',
    'payment_session_id',
    'payment_status',
    'payment_method'
];

public function user()
{
    return $this->belongsTo(User::class);
}

public function room()
{
    return $this->belongsTo(Room::class);
}



public function invoices()
{
    return $this->hasOne(Invoice::class);
}
public function bookingServices()

{
return $this->hasMany(BookingService::class, 'booking_id');
}

}