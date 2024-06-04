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
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'service_id' => 'required|exists:services,id',
        ]);

        $booking = Booking::find($request->booking_id);
        $service = Service::find($request->service_id);
        $totalPrice = $request->total_price;

        if ($booking && $service) {
            $booking->services()->attach($service->id, ['total_price' => $totalPrice]);
            $this->updateInvoice($booking);
            return $this->returnSuccessMessage('Service added successfully and invoice updated.');
        }

        return $this->returnError('E001', 'Failed to add service.');
    }

    private function updateInvoice(Booking $booking)
    {
        $totalAmount = $booking->services->sum('pivot.total_price');
        $invoice = $booking->invoices;

        if ($invoice) {
            $invoice->update(['total_amount' => $totalAmount]);
        } else {
            Invoice::create([
                'booking_id' => $booking->id,
                'total_amount' => $totalAmount
            ]);
        }
    }
   
    
}

