<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function showServices()
    {
        $services = Service::all();

        return re;
    }   
}

