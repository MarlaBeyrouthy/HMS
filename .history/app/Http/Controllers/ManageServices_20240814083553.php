<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Traits\GeneralTrait;


class ManageServices extends Controller
{
   use GeneralTrait;

    public function index()
    {
        $services = Service::all();
        return $this->returnData('All services retrieved successfully', $services);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'price' => 'required|numeric',
            'duration' => 'required|integer',
        ]);

        $service = Service::create($validated);

        return $this->returnData('Service created successfully', $service, Response::HTTP_CREATED);
    }

    public function show($id)
    {
        $service = Service::find($id);

        if (!$service) {
            return $this->returnErrorMessage('Service not found', 'S404', Response::HTTP_NOT_FOUND);
        }

        return $this->returnData('Service retrieved successfully', $service);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string|max:1000',
            'price' => 'sometimes|required|numeric',
            'duration' => 'sometimes|required|integer',
        ]);
    
        $service = Service::find($id);
    
        if (!$service) {
            return $this->returnErrorMessage('Service not found', 'S404', Response::HTTP_NOT_FOUND);
        }
    
        // تحديث الحقول المحددة فقط
        $service->update($validated);
    
        return $this->returnData('Service updated successfully', $service);
    }
    
    public function destroy($id)
    {
        $service = Service::find($id);

        if (!$service) {
            return $this->returnErrorMessage('Service not found', 'S404', Response::HTTP_NOT_FOUND);
        }

        $service->delete();

        return $this->returnSuccessMessage('Service deleted successfully');
    }
// تخصيص خدمة لحجز
public function assignServiceToBooking($serviceId, $bookingId)
{
    $service = Service::find($serviceId);
    $booking = Booking::find($bookingId);

    if (!$service || !$booking) {
        return $this->returnErrorMessage('Service or Booking not found', 'S404', Response::HTTP_NOT_FOUND);
    }

    // إضافة الخدمة إلى الحجز بكمية افتراضية 1
    $booking->services()->attach($serviceId, [
        'quantity' => 1,
        'total_price' => $service->price,
    ]);

    return $this->returnSuccessMessage('Service assigned to booking successfully');
}
   

      // إزالة تخصيص الخدمة من حجز
    public function removeServiceFromBooking($serviceId, $bookingId)
    {
        $service = Service::find($serviceId);
        $booking = Booking::find($bookingId);

        if (!$service || !$booking) {
            return $this->returnErrorMessage('Service or Booking not found', 'S404', Response::HTTP_NOT_FOUND);
        }

        $booking->services()->detach($serviceId);

        return $this->returnSuccessMessage('Service removed from booking successfully');
    }

     // عرض جميع الخدمات المخصصة لحجز
     public function getBookingServices($bookingId)
     {
         $booking = Booking::find($bookingId);
 
         if (!$booking) {
             return $this->returnErrorMessage('Booking not found', 'S404', Response::HTTP_NOT_FOUND);
         }
 
         $services = $booking->services;
 
         return $this->returnData('Booking services retrieved successfully', $services);
     }
}