<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'booking_id',
        'paid_amount',
        'remaining_amount',
        'total_amount', 
        'invoice_date',
       // 'taxes',
        'services',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class); 
    }
    public function bookingServices()
    {
        return $this->hasMany(BookingService::class, 'booking_id', 'booking_id');
    }

}