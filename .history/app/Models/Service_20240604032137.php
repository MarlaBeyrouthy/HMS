<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = ['service_name', 'price'];

    public function bookingServices()
    {
        return $this->hasMany(BookingService::class, 'service_id');
    }


}
