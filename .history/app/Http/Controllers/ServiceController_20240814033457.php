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
    public function requestService(Request $request)
    {
        $this->validate($request, [
            'booking_id' => 'required|exists:bookings,id',
            'service_id' => 'required|exists:services,id',
        ]);
    
        $booking = Booking::findOrFail($request->booking_id);
    
        $invoice = $booking->invoices;
        if (!$invoice) {
            return $this->returnError('E001', 'Invoice not found for the provided booking ID');
        }
    
        $service = Service::findOrFail($request->service_id);
    
        $invoice->total_amount += $service->price;
        $invoice->remaining_amount += $service->price;
    
        if ($invoice->payment_status == 'full_paid') {
            $invoice->payment_status = 'prePayment';
        }
    
        $booking->services()->attach($service->id, [
            'quantity' => 1, 
            'total_price' => $service->price,
        ]);
    
        $invoice->save();
    
        return $this->returnSuccessMessage('Service requested successfully');
    }
    public function showBookingServices($booking_id)
{
    $booking = Booking::findOrFail($booking_id);
    
    $services = $booking->services;
    
    return $this->returnData('services', $services);
}
public function cancelServiceRequest(Request $request)
{
    $this->validate($request, [
        'booking_id' => 'required|exists:bookings,id',
        'service_id' => 'required|exists:services,id',
    ]);git pull origin main

    $booking = Booking::findOrFail($request->booking_id);

    $invoice = $booking->invoices;
    if (!$invoice) {
        return $this->returnError('E001', 'Invoice not found for the provided booking ID');
    }

    $service = Service::findOrFail($request->service_id);

    if (!$booking->services()->where('service_id', $request->service_id)->exists()) {
        return $this->returnError('E002', 'Service not found in the booking');
    }

    $booking->services()->detach($service->id);

    $invoice->total_amount -= $service->price;
    $invoice->remaining_amount -= $service->price;

    if ($invoice->payment_status == 'prePayment' && $invoice->remaining_amount == 0) {
        $invoice->payment_status = 'full_paid';
    }

    $invoice->save();

    return $this->returnSuccessMessage('Service canceled successfully');
}
    
}


