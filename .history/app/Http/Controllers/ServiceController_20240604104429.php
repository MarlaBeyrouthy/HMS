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
        $invoice = Invoice::where('booking_id', $request->booking_id)->first();
        if (!$invoice) {
            return $this->returnError('E001', 'Invoice not found for the provided booking ID');
        }

        $service = Service::findOrFail($request->service_id);

        $invoice->total_amount += $service->price;
        $invoice->remaining_amount += $service->price;

        $services = $invoice->services ?? []; 
        if (!is_array($services)) {
            $services = json_decode($services, true) ?? [];
        }

        $services[] = $service->name;
        $invoice->services = json_encode($services);

        $invoice->save();

        return $this->returnSuccessMessage('Service requested successfully');
    }
}


