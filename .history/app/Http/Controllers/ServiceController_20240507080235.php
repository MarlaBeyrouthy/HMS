<?php

namespace App\Http\Controllers;

use App\Models\Booking;
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

        return $this->returnData($services,'');
    }

    public function reserveService(Request $request)
    {
        // قم بالتحقق من صحة البيانات المرسلة في الطلب
        $request->validate([
            'service_id' => 'required|exists:services,id',
            'booking_id' => 'required|exists:bookings,id',
            'quantity' => 'required|integer|min:1',
        ]);

        // استخراج المعلومات من الطلب
        $serviceId = $request->input('service_id');
        $bookingId = $request->input('booking_id');
        $quantity = $request->input('quantity');

        try {
            // ابحث عن الخدمة باستخدام المعرف
            $service = Service::findOrFail($serviceId);

            // ابحث عن الحجز باستخدام المعرف
            $booking = Booking::findOrFail($bookingId);

            // قم بإنشاء سجل "BookingService" الجديد
            $bookingService = new BookingService([
                'quantity' => $quantity,
                'total_price' => $service->price * $quantity,
            ]);

            // اربط الخدمة بالحجز
            $booking->services()->save($bookingService);

            return response()->json(['message' => 'تم حجز الخدمة بنجاح'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'حدث خطأ أثناء حجز الخدمة'], 500);
        }
    }
    
    
}

