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
use Illuminate\Support\Str;

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
            'payment_method' => 'required|in:cash,stripe', 
                
        ]);
    
        if ($validator->fails()) {
            return $this->returnErrorMessage($validator->errors()->first(), 'E001', 400);
        }
    
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
    
        if ($room->status == 'maintenance') {
            return $this->returnErrorMessage('The room is currently unavailable for booking.', 'E002', 400);
        }

        if($request->payment_method == 'stripe'){
        try {
            // إعداد المفتاح السري لـ Stripe وإنشاء جلسة دفع
            Stripe::setApiKey("sk_test_51NedBNEQbJiqtI6xmfmqk6fHT6g1DmnNWTncMeoGQLVYZn8e86HvEHBQk390lhS6fEYL4DDzTjT1sCKJhV2tZpN000krSm6HlX");
    
            // إنشاء معرف فريد كـ session_id
            $sessionId = Str::uuid()->toString();
            $paymentMethod = $request->payment_method;
            $session = Session::create([
                'payment_method_types' => [$paymentMethod == 'cash' ? 'cash' : 'card'],
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
                'success_url' => route('booking.success') . '?room_id=' . $request->room_id . '&check_in_date=' . $request->check_in_date . '&check_out_date=' . $request->check_out_date . '&num_adults=' . $request->num_adults . '&num_children=' . $request->num_children . '&session_id=' . $sessionId,
                'cancel_url' => route('booking.cancel'),
            ]);
            $checkOutDate = new \DateTime($request->check_out_date);
            $booking = Booking::create([
                'user_id' => Auth::id(),
                'room_id' => $request->room_id,
                'check_in_date' => $request->check_in_date,
                'check_out_date' => $request->check_out_date,
                'num_adults' => $request->num_adults,
                'num_children' => $request->num_children,
                'payment_status' => 'Pre_payment',
                'payment_session_id' => $sessionId,
               // 'session_expiry' => $checkOutDate,
                'payment_method' => $paymentMethod, 
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
                'session_id' => $sessionId,
                'Booking_id' => $booking->id,
                'invoice' => $invoice,
            ], 200);
        
    } 
        catch (\Exception $e) {
            // إرجاع رسالة خطأ في حال حدوث استثناء أثناء عملية الدفع
            return $this->returnErrorMessage($e->getMessage(), 'E003', 500);
        }
    }
    else
    {
        $booking = Booking::create([
            'user_id' => Auth::id(),
            'room_id' => $request->room_id,
            'check_in_date' => $request->check_in_date,
            'check_out_date' => $request->check_out_date,
            'num_adults' => $request->num_adults,
            'num_children' => $request->num_children,
            'payment_status' => 'Pre_payment', 
            'payment_method' => 'cash',     

        ]);
        $room->status = 'booked';
        $room ->save();
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

    }
    // إرجاع رسالة نجاح مع معرف الجلسة ومعرف الحجز ومعلومات الفاتورة
    return $this->returnData('Booking created successfully.', [
        'payment_method' => 'cash',
        'Booking_id' => $booking->id,
        'invoice' => $invoice,
    ], 200);
    }


    public function completePayment(Request $request)
    {
        // التحقق من صحة البيانات المدخلة
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|exists:bookings,id',
            'paid_amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,stripe',
        ]);
    
        if ($validator->fails()) {
            return $this->returnErrorMessage('Invalid input data.', 'E005', 400);
        }
    
        $paidAmount = $request->input('paid_amount');
        $paymentMethod = $request->input('payment_method');
        $booking = Booking::find($request->booking_id);
    
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
    
        // التحقق من طريقة الدفع المسبق
        $prePaymentMethod = $booking->payment_method;
    
        if ($prePaymentMethod === 'cash' && $paymentMethod === 'stripe') {
            // إذا كانت طريقة الدفع المسبق هي cash وطريقة الدفع المختارة هي stripe، نقوم بإنشاء جلسة Stripe جديدة
            try {
                // إعداد المفتاح السري لـ Stripe
                Stripe::setApiKey("sk_test_51NedBNEQbJiqtI6xmfmqk6fHT6g1DmnNWTncMeoGQLVYZn8e86HvEHBQk390lhS6fEYL4DDzTjT1sCKJhV2tZpN000krSm6HlX");
    
                // إنشاء معرف فريد كـ session_id
                $sessionId = Str::uuid()->toString();
                $session = Session::create([
                    'payment_method_types' => ['card'],
                    'line_items' => [
                        [
                            'price_data' => [
                                'currency' => 'usd',
                                'product_data' => [
                                    'name' => 'Booking',
                                ],
                                'unit_amount' => $remainingAmount * 100, // المبلغ المتبقي
                            ],
                            'quantity' => 1,
                        ],
                    ],
                    'mode' => 'payment',
                    'success_url' => route('booking.success') . '?booking_id=' . $request->booking_id . '&session_id=' . $sessionId,
                    'cancel_url' => route('booking.cancel'),
                ]);
    
                // تحديث الحجز بـ session_id الجديد
                $booking->payment_session_id = $sessionId;
                $booking->invoices->paid_amount += $paidAmount;
                $booking->invoices->remaining_amount -= $paidAmount;
        
                // تحديث حالة الدفع إلى fully_paid
                $booking->payment_status = 'fully_paid';
        
               
                $booking->invoices->save();
                $booking->save();
    
                // إرجاع معلومات الجلسة
                return $this->returnData('Stripe session created successfully.', [
                    'session_id' => $sessionId,
                    'username' => $booking->user->first_name,

                    'booking_id' => $booking->id,
                    'invoice' => $booking->invoices,
                ], 200);
            } catch (\Exception $e) {
                // إرجاع رسالة خطأ في حال حدوث استثناء أثناء عملية الدفع
                return $this->returnErrorMessage($e->getMessage(), 'E003', 500);
            }
        } elseif ($prePaymentMethod === 'stripe' && $paymentMethod === 'stripe') {
            // تحديث الحجز بـ session_id الجديد
           // $booking->payment_session_id = $sessionId;
            $booking->invoices->paid_amount += $paidAmount;
            $booking->invoices->remaining_amount -= $paidAmount;
    
            // تحديث حالة الدفع إلى fully_paid
            $booking->payment_status = 'fully_paid';
    
           
            $booking->invoices->save();
            $booking->save();
            // إذا كانت طريقة الدفع المسبق هي stripe وطريقة الدفع المختارة هي stripe، نستخدم الجلسة الحالية
            return $this->returnData('Continue with existing Stripe session.', [
                'session_id' => $booking->payment_session_id,
                'username' => $booking->user->first_name,
                'booking_id' => $booking->id,
                'invoice' => $booking->invoices,
            ], 200);
        } else {
            // في حالة الدفع نقداً (cash) يتم التحديث بدون إنشاء جلسة جديدة
            $booking->invoices->paid_amount += $paidAmount;
            $booking->invoices->remaining_amount -= $paidAmount;
    
            // تحديث حالة الدفع إلى fully_paid
            $booking->payment_status = 'fully_paid';
    
            // حفظ التعديلات في الحجز والفاتورة
            $booking->save();
            $booking->invoices->save();
    
            // إرجاع رسالة نجاح مع تفاصيل الدفع
            return $this->returnData('Payment completed successfully.', [
               // 'paid_amount' => $paidAmount,
               // 'remaining_amount' => $booking->invoices->remaining_amount,
                'payment_status' => $booking->payment_status,
                'username' => $booking->user->first_name,
                'room_number' => $booking->room->room_number,
                'invoice' => $booking->invoices,
            ], 200);
        }
    }
    
    public function showBookingDetails($id)
{
    $booking = Booking::with('room', 'user', 'invoices')->find($id);

    if (!$booking) {
        return $this->returnErrorMessage('Booking not found.', 'E006', 404);
    }

    return $this->returnData('Booking details retrieved successfully.', [
        'username' => $booking->user->first_name,
        'room_number' => $booking->room->room_number,
        'check_in_date' => $booking->check_in_date,
        'check_out_date' => $booking->check_out_date,
        'num_adults' => $booking->num_adults,
        'num_children' => $booking->num_children,
        'payment_status' => $booking->payment_status,
        'invoice' => $booking->invoices,
    ], 200);
}
public function getUserBookings()
{
    $user = Auth::user();
    $bookings = Booking::with('room', 'invoices')
                        ->where('user_id', $user->id)
                        ->get();

    if ($bookings->isEmpty()) {
        return $this->returnErrorMessage('No bookings found for the current user.', 'E009', 404);
    }
    return $this->returnData('User bookings retrieved successfully.', [
        'bookings' => $bookings,
    ], 200);
}


public function updateBooking(Request $request)
{
    // التحقق من صحة المدخلات
    $validator = Validator::make($request->all(), [
        'booking_id' => 'required|exists:bookings,id',
        'room_id' => 'required|exists:rooms,id',
        'check_in_date' => 'required|date',
        'check_out_date' => 'required|date|after_or_equal:check_in_date',
        'num_adults' => 'required|integer|min:1',
        'num_children' => 'required|integer|min:0',
    ]);

    if ($validator->fails()) {
        return $this->returnErrorMessage($validator->errors()->first(), 'E001', 400);
    }

    $booking = Booking::find($request->booking_id);
    if (!$booking) {
        return $this->returnErrorMessage('Booking not found.', 'E006', 404);
    }

    // التحقق من توافر الغرفة في التواريخ المحددة
    $existingBooking = Booking::where('room_id', $request->room_id)
        ->where(function ($query) use ($request) {
            $query->where('check_in_date', '<=', $request->check_out_date)
                  ->where('check_out_date', '>=', $request->check_in_date);
        })->first();

    if ($existingBooking && $existingBooking->id != $booking->id) {
        return $this->returnErrorMessage('The selected room is not available for the specified dates.', 'E002', 400);
    }

    // تحديث معلومات الحجز
    $booking->room_id = $request->room_id;
    $booking->check_in_date = $request->check_in_date;
    $booking->check_out_date = $request->check_out_date;
    $booking->num_adults = $request->num_adults;
    $booking->num_children = $request->num_children;
    $booking->save();

    $checkInDate = new \DateTime($booking->check_in_date);
    $checkOutDate = new \DateTime($booking->check_out_date);
    $numberOfNights = $checkInDate->diff($checkOutDate)->days;

    $taxRate = 0.1;

    $room = Room::find($request->room_id);
    if (!$room) {
        return $this->returnErrorMessage('Room not found.', 'E007', 404);
    }
    $basePricePerNight = $room->roomClass->base_price;
    $newBaseAmount = $basePricePerNight * $numberOfNights;

    $newTaxes = $newBaseAmount * $taxRate;

    $newTotalAmount = $newBaseAmount + $newTaxes;

    $paidAmount = $booking->invoices->sum('paid_amount');
    $newRemainingAmount = $newTotalAmount - $paidAmount;

    $invoice = $booking->invoices->first();
    $invoice->total_amount = $newTotalAmount;
    $invoice->remaining_amount = $newRemainingAmount;
    $invoice->taxes = $newTaxes;
    $invoice->invoice_date = now();
    $invoice->save();

    if ($newRemainingAmount > 0) {
        try {
            Stripe::setApiKey('sk_test_51NedBNEQbJiqtI6xmfmqk6fHT6g1DmnNWTncMeoGQLVYZn8e86HvEHBQk390lhS6fEYL4DDzTjT1sCKJhV2tZpN000krSm6HlX');

            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => 'Additional Payment for Booking',
                        ],
                        'unit_amount' => $newRemainingAmount * 100,
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('booking.success') . '?booking_id=' . $booking->id,
                'cancel_url' => route('booking.cancel'),
            ]);

            $booking->payment_session_id = $session->id;
            $booking->save();

            return $this->returnData('Additional payment required.', [
                'session_id' => $session->id,
                'username' => $booking->user->first_name,
                'booking_id' => $booking->id,
                'invoice' => $invoice,
            ], 200);
        } catch (\Exception $e) {
            return $this->returnErrorMessage($e->getMessage(), 'E003', 500);
        }
    } elseif ($newRemainingAmount < 0) {
        try {
            Stripe::setApiKey('sk_test_51NedBNEQbJiqtI6xmfmqk6fHT6g1DmnNWTncMeoGQLVYZn8e86HvEHBQk390lhS6fEYL4DDzTjT1sCKJhV2tZpN000krSm6HlX');

            // الحصول على معاملة الدفع الأصلية المرتبطة بالحجز
            $paymentIntent = \Stripe\PaymentIntent::retrieve($booking->payment_session_id);

            // إنشاء استرداد للفرق
            $refund = \Stripe\Refund::create([
                'amount' => abs($newRemainingAmount) * 100,
                'payment_intent' => $paymentIntent->id,
            ]);

            $invoice->paid_amount = $newTotalAmount;
            $invoice->remaining_amount = 0;
            $invoice->save();

            return $this->returnData('Refund processed successfully.', [
                'refund_id' => $refund->id,
                'username' => $booking->user->first_name,
                'booking_id' => $booking->id,
                'invoice' => $invoice,
            ], 200);
        } catch (\Exception $e) {
            return $this->returnErrorMessage($e->getMessage(), 'E003', 500);
        }
    }

    return $this->returnData('Booking updated successfully.', [
        'booking_id' => $booking->id,
        'room_number' => $booking->room->room_number,
        'check_in_date' => $booking->check_in_date,
        'check_out_date' => $booking->check_out_date,
        'num_adults' => $booking->num_adults,
        'num_children' => $booking->num_children,
        'invoice' => $invoice,
    ], 200);
}

   
    public function viewInvoice(Request $request)
    {
        $this->validate($request, [
            'id' => 'required',
        ]);
        $booking = Booking::where('id', $request->id)->first();
    
        if (!$booking) {
            return $this->returnErrorMessage('Booking not found.', 'error', 404);
        }
     if ($booking->user_id !== auth()->id()) {
        return $this->returnErrorMessage('Unauthorized access.', 'error', 403);
    }
        $invoice = $booking->invoices;
    
        $username = $booking->user->first_name;
        $roomNumber = $booking->room->room_number;
        $num_adults = $booking->num_adults;
        $num_children = $booking->num_children;
        return $this->returnData('Invoice details.', [
            'username' => $username,
            'room_number' => $roomNumber,
            'num_adults'=>$num_adults,
            'num_children' => $num_children,
            'invoice' => $invoice,
        ]);
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

