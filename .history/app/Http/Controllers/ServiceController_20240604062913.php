<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Service;
use Illuminate\Http\Request;
use App\Models\BookingService;
use App\Http\Traits\GeneralTrait;

class ServiceController extends Controller
{
    use GeneralTrait;
    public function showServices()
    {
        $services = Service::all();

        return $this->returnData('',$services);
    }
    public function requestService(Request $request, $bookingId)
    {
        $request->validate([
            'service_id' => 'required|exists:services,id',
        ]);

        $booking = Booking::findOrFail($bookingId);
        $service = Service::findOrFail($request->service_id);
        $totalPrice = $service->price; // السعر المحدد للخدمة

        $booking->services()->attach($service->id, ['total_price' => $totalPrice]);

        $booking->updateInvoice(); // تحديث الفاتورة

        return response()->json(['message' => 'Service added successfully and invoice updated.']);
    }
    
}

