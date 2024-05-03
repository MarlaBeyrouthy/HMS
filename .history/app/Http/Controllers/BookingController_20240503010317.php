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
    

    
    public function makeBooking(Request $request)
    {
        // التحقق من صحة البيانات المدخلة
        $this->validate($request, [
            'room_id' => 'required|exists:rooms,id',
            'check_in_date' => 'required|date',
            'check_out_date' => 'required|date|after_or_equal:check_in_date',
            'num_adults' => 'required|integer|min:1',
            'num_children' => 'required|integer|min:0',
        ]);
        // البحث عن حجز موجود بنفس الغرفة وفترة الوقت المطلوبة
        $existingBooking = Booking::where('room_id', $request->room_id)
            ->where(function ($query) use ($request) {
                $query->where('check_in_date', '<=', $request->check_out_date)
                    ->where('check_out_date', '>=', $request->check_in_date);
            })->first();
        // إذا وُجد حجز موجود وكانت حالة الدفع 'cancel'، يتم حذف الحجز
        if ($existingBooking && $existingBooking->payment_status == 'cancel') {
            $existingBooking->delete();
        }
        // إذا وُجد حجز موجود وكانت حالة الدفع غير 'cancel'، يتم إرجاع رسالة خطأ
        if ($existingBooking && $existingBooking->payment_status != 'cancel') {
            return $this->returnErrorMessage('There is already a booking for the selected dates.', 'E001', 400);
        }
        // البحث عن الغرفة المطلوبة 
        $room = Room::findOrFail($request->room_id);
        // إذا كانت حالة الغرفة 'maintenance'، يتم إرجاع رسالة خطأ
        if ($room->status == 'maintenance') {
            return $this->returnErrorMessage('The room is currently unavailable for booking.', 'E002', 400);
        }
        // إنشاء الحجز بعد التحقق من جميع الشروط
        $booking = Booking::create([
            'user_id' => Auth::id(),
            'room_id' => $request->room_id,
            'check_in_date' => $request->check_in_date,
            'check_out_date' => $request->check_out_date,
            'num_adults' => $request->num_adults,
            'num_children' => $request->num_children,
            'payment_status' => 'not_paid',
        ]);
        try {
            // إعداد المفتاح السري لـ Stripe وإنشاء جلسة دفع
            Stripe::setApiKey(env('STRIPE_SECRET'));
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => 'usd',
                            'product_data' => [
                                'name' => 'Booking',
                            ],
                            'unit_amount' =>  $room->roomClass->base_price * 0.25 * 100,
                        ],
                        'quantity' => 1,
                    ],
                ],
                'mode' => 'payment',
                'success_url' => route('booking.success'),
                'cancel_url' => route('booking.cancel'),
            ]);

            // حفظ معرف الجلسة في الحجز
            $booking->payment_session_id = $session->id;
            $booking->save();
            // إرجاع رسالة نجاح مع معرف الجلسة ومعرف الحجز
            return $this->returnData('Booking created successfully.', [
                'session_id' => $session->id,
                'Booking_id' => $booking->id,
            ], 200);
        } catch (\Exception $e) {
            // إرجاع رسالة خطأ في حال حدوث استثناء أثناء عملية الدفع
            return $this->returnErrorMessage($e->getMessage(), 'E003', 500);
        }
    }


    
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
