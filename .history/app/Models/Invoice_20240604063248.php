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
        'services_id',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class); 
    }
  
}