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

        return $this->returnData($services,'');
    }
    
    public function requestServices(Request $request)
    {
        // استلام بيانات الحجز من الجسم
        $bookingId = $request->input('booking_id');
        $serviceIds = $request->input('service_ids');
        $quantities = $request->input('quantities');
    
        // العثور على سجل الحجز
        $booking = Booking::find($bookingId);
    
        if ($booking) {
            // الوصول إلى الغرفة المحجوزة
            $room = $booking->room;
    
            if ($room) {
                $roomId = $room->id;
                $roomName = $room->name;
                // يمكنك استخدام الغرفة المحجوزة هنا بالطريقة التي تحتاجها
                // ...
            } else {
                // الغرفة غير موجودة
            }
    
            // إضافة الخدمات المحددة إلى الحجز
            foreach ($serviceIds as $index => $serviceId) {
                $quantity = $quantities[$index];
    
                // العثور على الخدمة
                $service = Service::find($serviceId);
    
                if ($service) {
                    // إضافة الخدمة إلى الحجز
                    $booking->services()->attach($service, [
                        'quantity' => $quantity,
                        'total_price' => $service->price * $quantity,
                    ]);
                }
            }
    
            // إرجاع البيانات بنجاح
            return response()->json([
                'message' => 'تم طلب الخدمات بنجاح.',
                'booking_id' => $booking->id,
            ]);
        } else {
            // سجل الحجز غير موجود
            return response()->json(['message' => 'سجل الحجز غير موجود.'], 404);
        }
    }
}

