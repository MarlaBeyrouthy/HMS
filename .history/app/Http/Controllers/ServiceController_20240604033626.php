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
        $bookingId = $request->input('booking_id');
        $serviceId = $request->input('service_id');
        $quantity = $request->input('quantity');
    
        // Find the booking and service
        $booking = Booking::find($bookingId);
        $service = Service::find($serviceId);
    
        if (!$booking || !$service) {
            return $this->returnError('Booking or Service not found', 404);
        }
    
        // Check if the authenticated user is the owner of the booking
        $user = auth()->user();
        if ($booking->user_id !== $user->id) {
            return $this->returnError('Unauthorized', 401);
        }
    
        // Calculate total price for the service
        $servicePrice = $service->price;
        $totalPrice = $servicePrice * $quantity;
    
        // Attach the service to the booking
        $booking->bookingServices()->create([
            'service_id' => $serviceId,
            'quantity' => $quantity,
            'total_price' => $totalPrice
        ]);
    
        // Update the invoice
        $invoice = $booking->invoice;
        if ($invoice) {
            $invoice->total_amount += $totalPrice;
            $invoice->remaining_amount += $totalPrice;
            $services = json_decode($invoice->services);
            $services[] = [
                'service_name' => $service->service_name,
                'service_price' => $servicePrice
            ];
            $invoice->services = json_encode($services);
            $invoice->save();
        } else {
            $invoice = Invoice::create([
                'booking_id' => $bookingId,
                'paid_amount' => 0,
                'remaining_amount' => $totalPrice,
                'total_amount' => $totalPrice,
                'invoice_date' => now(),
                'services' => json_encode([
                    [
                        'service_name' => $service->service_name,
                        'service_price' => $servicePrice
                    ]
                ])
            ]);
    
            // Associate the invoice with the booking
            $booking->invoices()->associate($invoice);
            $booking->save();
        }
    
        return $this->returnSuccessMessage('Service requested successfully');
    }
}

