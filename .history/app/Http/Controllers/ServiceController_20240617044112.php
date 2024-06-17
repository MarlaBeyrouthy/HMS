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
        ]);
    
        // البحث عن الحجز باستخدام booking_id
        $booking = Booking::findOrFail($request->booking_id);
    
        // البحث عن الفاتورة المرتبطة بالحجز
        $invoice = $booking->invoices;
        if (!$invoice) {
            return $this->returnError('E001', 'Invoice not found for the provided booking ID');
        }
    
        // العثور على الخدمة المطلوبة
        $service = Service::findOrFail($request->service_id);
    
        // تحديث المبالغ في الفاتورة
        $invoice->total_amount += $service->price;
        $invoice->remaining_amount += $service->price;
    
        // تحقق من حالة الدفع وتحديثها إذا كانت full_paid
        if ($invoice->payment_status == 'full_paid') {
            $invoice->payment_status = 'prePayment';
        }
    
        // إضافة الخدمة إلى الحجز
        $booking->services()->attach($service->id, [
            'quantity' => 1, // يمكنك تعديل الكمية إذا لزم الأمر
            'total_price' => $service->price,
        ]);
    
        // حفظ الفاتورة
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
    // التحقق من صحة البيانات
    $this->validate($request, [
        'booking_id' => 'required|exists:bookings,id',
        'service_id' => 'required|exists:services,id',
    ]);

    // البحث عن الحجز باستخدام booking_id
    $booking = Booking::findOrFail($request->booking_id);

    // البحث عن الفاتورة المرتبطة بالحجز
    $invoice = $booking->invoices;
    if (!$invoice) {
        return $this->returnError('E001', 'Invoice not found for the provided booking ID');
    }

    // العثور على الخدمة المطلوبة
    $service = Service::findOrFail($request->service_id);

    // التأكد من أن الخدمة موجودة في الحجز
    if (!$booking->services()->where('service_id', $request->service_id)->exists()) {
        return $this->returnError('E002', 'Service not found in the booking');
    }

    // إزالة الخدمة من الحجز
    $booking->services()->detach($service->id);

    // تحديث المبالغ في الفاتورة
    $invoice->total_amount -= $service->price;
    $invoice->remaining_amount -= $service->price;

    // تحقق من حالة الدفع وتحديثها إذا كانت prePayment وتغيرت المبالغ
    if ($invoice->payment_status == 'prePayment' && $invoice->remaining_amount == 0) {
        $invoice->payment_status = 'full_paid';
    }

    // حفظ الفاتورة
    $invoice->save();

    return $this->returnSuccessMessage('Service canceled successfully');
}
    
}


