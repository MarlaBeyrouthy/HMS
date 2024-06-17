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
        // التحقق من صحة البيانات
        $this->validate($request, [
            'booking_id' => 'required|exists:bookings,id',
            'service_id' => 'required|exists:services,id',
            'quantity' => 1, // يمكنك تعديل الكمية إذا لزم الأمر

        ]);

        // البحث عن الفاتورة باستخدام booking_id
        $invoice = Invoice::where('booking_id', $request->booking_id)->first();
        if (!$invoice) {
            return $this->returnError('E001', 'Invoice not found for the provided booking ID');
        }

        // العثور على الخدمة المطلوبة
        $service = Service::findOrFail($request->service_id);

        // تحديث المبالغ في الفاتورة
        $invoice->total_amount += $service->price;
        $invoice->remaining_amount += $service->price;

        // إضافة الخدمة إلى الحجز
        $booking = $invoice->booking; // احصل على الحجز من الفاتورة
        $booking->services()->attach($service->id, [
            'total_price' => $service->price,
        ]);

        // حفظ الفاتورة
        $invoice->save();

        return $this->returnSuccessMessage('Service requested successfully');
    }
}


