<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function showServices()
    {
        $services = Service::all();

        return view('services', compact('services'));
    }   
}

