<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable = ['user_id', 'title', 'text_description', 'is_checked'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }}
