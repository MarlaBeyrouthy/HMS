<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'personal_id',
        'email',
        'phone',
        'picture',
        'place_of_residence',

    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
       // 'password' => 'hashed',
    ];

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
    public function wishlists()
    {
        return $this->belongsToMany(Room::class, 'wishlists');
    }
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
    public function reports()
    {
        return $this->hasMany(Report::class);
    }
    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }
}
// "laravel/framework": "^10.10",
