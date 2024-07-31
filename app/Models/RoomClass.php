<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class RoomClass extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'class_name', 'base_price', 'bed_type', 'number_of_beds'
    ];

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }
}
