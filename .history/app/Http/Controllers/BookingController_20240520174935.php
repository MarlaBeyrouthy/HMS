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
use Illuminate\Support\Facades\Validator;

class BookingController extends Controller
{
    use GeneralTrait;
    

    
    class BookingController extends Controller
{
    use GeneralTrait;
    
    public function makeBooking(Request $request)
    {
        // التحقق من صحة البيانات المدخلة
        $validator = Validator::make($request->all(), [
            'room_id' => 'required|exists:rooms,id',
            'check_in_date' => 'required|date',
            'check_out_date' => 'required|date|after_or_equal:check_in_date',
            'num_adults' => 'required|integer|min:1',
            'num_children' => 'required|integer|min:0',
        ]);
    
        if ($validator->fails()) {
            return $this->returnErrorMessage($validator->errors()->first(), 'E001', 400);
        }
    
        // البحث عن الغرفة المطلوبة 
        $room = Room::findOrFail($request->room_id);
    
        if ($room->status == 'maintenance') {
            return $this->returnErrorMessage('The room is currently unavailable for booking.', 'E002', 400);
        }
    
        // التحقق من وجود حجز موجود بنفس الغرفة وفترة الوقت المطلوبة
        $existingBooking = Booking::where('room_id', $request->room_id)
            ->where(function ($query) use ($request) {
                $query->where('check_in_date', '<=', $request->check_out_date)
                    ->where('check_out_date', '>=', $request->check_in_date);
            })->first();
    
        // إذا وُجد حجز موجود في نفس الوقت، يتم إرجاع رسالة خطأ
        if ($existingBooking) {
            return $this->returnErrorMessage('The room is already booked for the selected dates.', 'E004', 400);
        }
    
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
                'success_url' => route('booking.success') . '?room_id=' . $request->room_id . '&check_in_date=' . $request->check_in_date . '&check_out_date=' . $request->check_out_date . '&num_adults=' . $request->num_adults . '&num_children=' . $request->num_children . '&session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('booking.cancel'),
            ]);
    
            // إنشاء الحجز بعد التحقق من جميع الشروط
            $booking = Booking::create([
                'user_id' => Auth::id(),
                'room_id' => $request->room_id,
                'check_in_date' => $request->check_in_date,
                'check_out_date' => $request->check_out_date,
                'num_adults' => $request->num_adults,
                'num_children' => $request->num_children,
                'payment_status' => 'Pre_payment',
                'payment_session_id' => $session->id, // حفظ معرف الجلسة في الحجز
            ]);
            
            // حساب عدد الليالي
            $checkInDate = new \DateTime($booking->check_in_date);
            $checkOutDate = new \DateTime($booking->check_out_date);
            $numberOfNights = $checkInDate->diff($checkOutDate)->days;

            // حساب معدل الضريبة
            $taxRate = 0.1;

            $roomClass = $room->roomClass;
            $basePricePerNight = $roomClass->base_price;

            $paidAmount = ($basePricePerNight * $numberOfNights) / 4;
            // حساب المبلغ المتبقي بعد دفع العربون
            $remainingAmount = ($basePricePerNight * $numberOfNights) - $paidAmount;

            // إنشاء أو تحديث الفاتورة
            $invoice = Invoice::updateOrCreate(
                ['booking_id' => $booking->id],
                [
                    'total_amount' => $basePricePerNight * $numberOfNights,
                    'paid_amount' => $paidAmount,
                    'remaining_amount' => $remainingAmount,
                    'taxes' => $basePricePerNight * $numberOfNights * $taxRate,
                    'invoice_date' => now(),
                ]
            );
    
            // إرجاع رسالة نجاح مع معرف الجلسة ومعرف الحجز ومعلومات الفاتورة
            return $this->returnData('Booking created successfully.', [
                'session_id' => $session->id,
                'Booking_id' => $booking->id,
                'invoice' => $invoice,
            ], 200);
        } catch (\Exception $e) {
            // إرجاع رسالة خطأ في حال حدوث استثناء أثناء عملية الدفع
            return $this->returnErrorMessage($e->getMessage(), 'E003', 500);
        }
    }

    public function viewInvoice(Request $request)
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
    
        // الحصول على الفاتورة المرتبطة بالحجز
        $invoice = $booking->invoices;
    
        // جلب اسم المستخدم ورقم الغرفة
        $username = $booking->user->first_name;
        $roomNumber = $booking->room->room_number;
    
        return $this->returnData('Invoice details.', [
            'username' => $username,
            'room_number' => $roomNumber,
            'invoice' => $invoice
        ]);
    }
    


    public function completePayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'paid_amount' => 'required|numeric|min:0',
        ]);
    
        if ($validator->fails()) {
            return $this->returnErrorMessage('Invalid paid amount.', 'E005', 400);
        }
    
        $paidAmount = $request->input('paid_amount');
    
        // التحقق من وجود الحجز المرتبط بمعرف الجلسة
        $booking = Booking::where('payment_session_id', $request->session_id)->first();
    
        if (!$booking) {
            return $this->returnErrorMessage('Booking not found.', 'E006', 404);
        }
    
        // التحقق من حالة الدفع الحالية للحجز
        if ($booking->payment_status !== 'Pre_payment') {
            return $this->returnErrorMessage('Payment has not been initiated yet.', 'E004', 400);
        }
    
        // حساب المبلغ المتبقي للدفع
        $remainingAmount = $booking->invoices->remaining_amount;
    
        // التحقق مما إذا كان المبلغ المدفوع يساوي المبلغ المتبقي
        if ($paidAmount != $remainingAmount) {
            return $this->returnErrorMessage('Paid amount does not match remaining amount.', 'E007', 400);
        }
    
        // تحديث الفاتورة بالمبلغ المدفوع الفعلي وتحديث المبلغ المتبقي
        $booking->invoices->paid_amount += $paidAmount;
        $booking->invoices->remaining_amount -= $paidAmount;
    
        // تحديث حالة الدفع إلى Paid
        $booking->payment_status = 'fully_paid';
    
        // حفظ التعديلات في الحجز والفاتورة
        $booking->save();
        $booking->invoices->save();
    
        // إرجاع رسالة نجاح مع تفاصيل الدفع
        return $this->returnData('Payment completed successfully.', [
            'paid_amount' => $paidAmount,
            'remaining_amount' => $booking->invoices->remaining_amount,
            'payment_status' => $booking->payment_status,
            'username' => $booking->user->first_name,
            'room_number' => $booking->room->room_number,
            'invoice' => $booking->invoice,
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