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
        // استلام بيانات الخدمات المطلوبة من المستخدم
        $serviceIds = $request->input('service_ids', []);
        $quantities = $request->input('quantities', []);

        // إنشاء حجز جديد
        $booking = new Booking();
        $booking->user_id = auth()->user()->id; // افتراضي أنه تم تسجيل الدخول للمستخدم
        $booking->save();

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
    }
}

