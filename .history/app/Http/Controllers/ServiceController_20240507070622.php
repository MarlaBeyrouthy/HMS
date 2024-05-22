<?php

namespace App\Http\Controllers;

use App\Http\Traits\GeneralTrait;
use App\Models\Booking;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    use GeneralTrait;
    public function showServices()
    {
        $services = Service::all();

        return ;
    }   
}

