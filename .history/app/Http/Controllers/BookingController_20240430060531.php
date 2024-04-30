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

    //البحث عن الغرفة المطلوبة 
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
        // المفتاح السري الموقع بيعطينا ياه ومنخزنو بال.e
        Stripe::setApiKey('sk_test_51NedBNEQbJiqtI6xmfmqk6fHT6g1DmnNWTncMeoGQLVYZn8e86HvEHBQk390lhS6fEYL4DDzTjT1sCKJhV2tZpN000krSm6HlX');
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

    
    public function handlePaymentSuccess(Request $request)
    {
        $booking = Booking::where('payment_session_id', $request->session_id)->first();
        if (!$booking) {
            return $this->returnErrorMessage('Booking not found.', 'error', 404);
        }
        $booking->payment_status = 'Pre_payment';
        $booking->save();
        $taxRate = 0.1;
        $room = $booking->room;
        $room->status = 'booked';
        $room->save();
        $remainingAmount = $booking->room->roomClass->base_price - ($booking->room->roomClass->base_price * 0.25);
        
        $invoice = Invoice::firstOrCreate
        (
            ['booking_id' => $booking->id],
            [
                'total_amount' => $booking->room->roomClass->base_price + ($booking->room->roomClass->base_price * $taxRate),
                'paid_amount' => $booking->room->roomClass->base_price * 0.25,
                'remaining_amount' => $remainingAmount,
                'taxes' => $booking->room->roomClass->base_price * $taxRate,
                'invoice_date' => now(),
            ]
        );
        $username = $booking->user->first_name;
        $roomNumber = $booking->room->room_number;
        return $this->returnData('Payment successful.', 
        [
            'username' => $username,
            'room_number' => $roomNumber,
            'invoice' => $invoice
        ]);
    }



    public function completePayment(Request $request)
    {
        $booking = Booking::where('id', $request->booking_id)->first();
        if ($booking && $booking->payment_status == 'Pre_payment') 
        {
            $remainingAmount = $booking->room->roomClass->base_price - ($booking->room->roomClass->base_price * 0.25);
            $booking->payment_status = 'fully_paid';
            $booking->save();
            $invoice = $booking->invoice;
            if ($invoice) 
            {
                $invoice->paid_amount = $invoice->total_amount;
                $invoice->remaining_amount = 0;
                $invoice->invoice_date = now();
                $invoice->save();
            } else 
            {
                $taxRate = 0.1;
                $totalAmount = $booking->room->roomClass->base_price + ($booking->room->roomClass->base_price * $taxRate);
                Invoice::create
                ([
                    'booking_id' => $booking->id,
                    'total_amount' => $totalAmount,
                    'paid_amount' => $totalAmount,
                    'remaining_amount' => 0,
                    'taxes' => $totalAmount * $taxRate,
                    'invoice_date' => now(),
                ]);
            }
            $username = $booking->user->first_name;
            $roomNumber = $booking->room->room_number;
            return $this->returnData('Payment completed successfully.', 
            [
                'username' => $username,
                'room_number' => $roomNumber,
                'invoice' => $invoice
            ]);
        } 
        else 
        {
            return $this->returnErrorMessage('Booking not found or payment not completed.', 'error', 404);    
        }
    }
    
    
    
    public function cancelBooking(Request $request)
        {
            $booking = Booking::find($request->booking_id);
            if ($booking && $booking->payment_status == 'Pre_payment') 
            {
                $booking->room->status = 'available';
                $booking->room->save();
                $booking->payment_status = 'cancel';
                $booking->save();
                // $booking->delete();
                return $this->returnSuccessMessage('Booking canceled successfully.');
                } else 
                {
                    return $this->returnErrorMessage('Booking not found or already canceled.', 'error', 404);
                }
        }
    
}