<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;


class Room extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'floor', 'status', 'room_number', 'room_class_id', 'average_rating'
    ];

    public function roomClass()
    {
        return $this->belongsTo(RoomClass::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
    public function wishlistedBy()
    {
        return $this->belongsToMany(User::class, 'wishlists');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}

