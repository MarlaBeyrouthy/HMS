<?php

namespace App\Http\Controllers;

use Stripe\Stripe;
use App\Models\Room;
use App\Models\Booking;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Stripe\Checkout\Session;
use App\Http\Traits\GeneralTrait;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    use GeneralTrait;
    


   
    //تابع للدفع المسبق 
    public function handlePaymentSuccess(Request $request)
    {
        // التحقق من وجود معرف الجلسة
        $this->validate($request, [
            'session_id' => 'required',
        ]);

        // البحث عن الحجز المرتبط بمعرف الجلسة
        $booking = Booking::where('payment_session_id', $request->session_id)->first();
        // إذا لم يتم العثور على الحجز، يتم إرجاع رسالة خطأ
        if (!$booking) {
            return $this->returnErrorMessage('Booking not found.', 'error', 404);
        }

        // تحديث حالة الدفع إلى "Pre_payment"
        $booking->payment_status = 'Pre_payment';
        $booking->save();
        // حساب معدل الضريبة
        $taxRate = 0.1;
        $room = $booking->room;

        $room->status = 'booked'; // تحديث حالة الغرفة إلى "booked"
        $room->save();
        // حساب المبلغ المتبقي بعد دفع العربون
        $remainingAmount = $booking->room->roomClass->base_price - ($booking->room->roomClass->base_price * 0.25);

        // إنشاء أو تحديث الفاتورة
        $invoice = Invoice::updateOrCreate(
            ['booking_id' => $booking->id],
            [
                'total_amount' => $booking->room->roomClass->base_price + ($booking->room->roomClass->base_price * $taxRate),
                'paid_amount' => $booking->room->roomClass->base_price * 0.25,
                'remaining_amount' => $remainingAmount,
                'taxes' => $booking->room->roomClass->base_price * $taxRate,
                'invoice_date' => now(),
            ]
        );
        // جلب اسم المستخدم ورقم الغرفة وإرجاعها
        $username = $booking->user->first_name;
        $roomNumber = $booking->room->room_number;
        return $this->returnData('Payment successful.', [
            'username' => $username,
            'room_number' => $roomNumber,
            'invoice' => $invoice
        ]);
    }



    public function completePayment($paidAmount)
{
    // التحقق مما إذا كانت الحالة الحالية للدفع Pre_payment
    if ($this->payment_status !== 'Pre_payment') {
        // إذا لم تكن الحالة Pre_payment، يتم إرجاع رسالة خطأ
        return $this->returnErrorMessage('Payment has not been initiated yet.', 'E004', 400);
    }

    // تحديث الفاتورة بمبلغ الدفع المسبق
    $this->invoice->paid_amount += $paidAmount;
    $this->invoice->remaining_amount -= $paidAmount;
    // إذا تم دفع كامل المبلغ المتبقي، تحديث حالة الدفع إلى Paid
    if ($this->invoice->remaining_amount <= 0) {
        $this->payment_status = 'Paid';
    }
    $this->save();
    $this->invoice->save();

    // إرجاع رسالة نجاح مع تفاصيل الدفع
    return $this->returnData('Payment completed successfully.', [
        'paid_amount' => $paidAmount,
        'remaining_amount' => $this->invoice->remaining_amount,
        'payment_status' => $this->payment_status,
    ], 200);
}



    public function cancelBooking(Request $request)
    {
        // التحقق من صحة البيانات المدخلة
        $this->validate($request, [
            'booking_id' => 'required|exists:bookings,id',
        ]);

        // البحث عن الحجز بناءً على معرف الحجز
        $booking = Booking::find($request->booking_id);
        if ($booking && $booking->payment_status == 'Pre_payment') {
            // تحديث حالة الغرفة إلى "available"
            $booking->room->status = 'available';
            $booking->room->save();
            // تحديث حالة الدفع إلى "cancel"
            $booking->payment_status = 'cancel';
            $booking->save();
            // حذف الحجز (اختياري)
            // $booking->delete();
            return $this->returnSuccessMessage('Booking canceled successfully.');
        } else {
            return $this->returnErrorMessage('Booking not found or already canceled.', 'error', 404);
        }
    }
}
