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
        // التحقق من وجود فاتورة مرتبطة بالحجز
        $invoice = Invoice::where('booking_id', $request->booking_id)->first();
        if (!$invoice) {
            return $this->returnError('E001', 'Invoice not found for the provided booking ID');
        }

        // التحقق من وجود الخدمة المطلوبة
        $service = Service::findOrFail($request->service_id);

        // تحديث الفاتورة بناءً على تكلفة الخدمة
        $invoice->total_amount += $service->price;
        $invoice->remaining_amount += $service->price;

        // إضافة اسم الخدمة إلى حقل service
        $services = $invoice->service;
        if (is_null($services)) {
            $services = [];
        }
        $services[] = $service->name;
        $invoice->service = $services;

        $invoice->save();

        return $this->returnSuccessMessage('Service requested successfully');
    }
}


