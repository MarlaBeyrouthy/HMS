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
    
        $room = Room::findOrFail($request->room_id);
    
        if ($room->status == 'maintenance') {
            return $this->returnErrorMessage('The room is currently unavailable for booking.', 'E002', 400);
        }

        if($request->payment_method == 'stripe'){
        try {
            Stripe::setApiKey("sk_test_51NedBNEQbJiqtI6xmfmqk6fHT6g1DmnNWTncMeoGQLVYZn8e86HvEHBQk390lhS6fEYL4DDzTjT1sCKJhV2tZpN000krSm6HlX");
    
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
    
            $totalBaseAmount = $basePricePerNight * $numberOfNights;

            $taxes = $totalBaseAmount * $taxRate;

            $paidAmount = $totalBaseAmount / 4;

            $remainingAmount = $totalBaseAmount - $paidAmount + $taxes;

            $totalAmountWithTaxes = $totalBaseAmount + $taxes;

            $invoice = Invoice::updateOrCreate(
                ['booking_id' => $booking->id],
                [
                    'total_amount' => $totalAmountWithTaxes,
                    'paid_amount' => $paidAmount,
                    'remaining_amount' => $remainingAmount,
                    'taxes' => $taxes,
                    'invoice_date' => now(),
                ]
            );
            $invoiceData = $invoice->only(['taxes','total_amount', 'paid_amount', 'remaining_amount']);
            return $this->returnData('Booking created successfully.', [
                'payment_method' => $paymentMethod, 
                'session_id' => $sessionId,
                'Booking_id' => $booking->id,
                'invoice' => $invoiceData,
            ], 200);
        
    } 
        catch (\Exception $e) {
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
         $checkInDate = new \DateTime($booking->check_in_date);
         $checkOutDate = new \DateTime($booking->check_out_date);
         $numberOfNights = $checkInDate->diff($checkOutDate)->days;
 
         $taxRate = 0.1;
 
         $roomClass = $room->roomClass;
         $basePricePerNight = $roomClass->base_price;
 
         $totalBaseAmount = $basePricePerNight * $numberOfNights;

         $taxes = $totalBaseAmount * $taxRate;

         $paidAmount = $totalBaseAmount / 4;

         $remainingAmount = $totalBaseAmount - $paidAmount + $taxes;

         $totalAmountWithTaxes = $totalBaseAmount + $taxes;

         $invoice = Invoice::updateOrCreate(
             ['booking_id' => $booking->id],
             [
                'total_amount' => $totalAmountWithTaxes,
                'paid_amount' => $paidAmount,
                'remaining_amount' => $remainingAmount,
                'taxes' => $taxes,
                'invoice_date' => now(),
             ]
         );

    }
    $invoiceData = $invoice->only(['taxes','total_amount', 'paid_amount', 'remaining_amount']);

    return $this->returnData('Booking created successfully.', [
        'payment_method' => 'cash',
        'Booking_id' => $booking->id,
        'invoice' => $invoiceData,
    ], 200);
    }


    public function completePayment(Request $request)
    {
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
    
        if ($booking->payment_status !== 'Pre_payment') {
            return $this->returnErrorMessage('Payment has not been initiated yet.', 'E004', 400);
        }
    
        // حساب المبلغ المتبقي للدفع
        $remainingAmount = $booking->invoices->remaining_amount;
    
        if ($paidAmount != $remainingAmount) {
            return $this->returnErrorMessage('Paid amount does not match remaining amount.', 'E007', 400);
        }
    
        // التحقق من طريقة الدفع المسبق
        $prePaymentMethod = $booking->payment_method;
    
        if ($prePaymentMethod === 'cash' && $paymentMethod === 'stripe') {
            try {
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
                                'unit_amount' => $remainingAmount * 100, 
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
        
                $booking->payment_status = 'fully_paid';
        
               
                $booking->invoices->save();
                $booking->save();
    
                return $this->returnData('Stripe session created successfully.', [
                    'session_id' => $sessionId,
                    'username' => $booking->user->first_name,

                    'booking_id' => $booking->id,
                    'invoice' => $booking->invoices,
                ], 200);
            } catch (\Exception $e) {
                return $this->returnErrorMessage($e->getMessage(), 'E003', 500);
            }
        } elseif ($prePaymentMethod === 'stripe' && $paymentMethod === 'stripe') {
            // تحديث الحجز بـ session_id الجديد
           // $booking->payment_session_id = $sessionId;
            $booking->invoices->paid_amount += $paidAmount;
            $booking->invoices->remaining_amount -= $paidAmount;
    
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
    
            $booking->payment_status = 'fully_paid';
    
            $booking->save();
            $booking->invoices->save();
    
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

    $room = Room::findOrFail($request->room_id);
    if ($room->status == 'maintenance') {
        return $this->returnErrorMessage('The room is currently unavailable for booking.', 'E002', 400);
    }

    $originalCheckInDate = new \DateTime($booking->check_in_date);
    $originalCheckOutDate = new \DateTime($booking->check_out_date);
    $originalNumberOfNights = $originalCheckInDate->diff($originalCheckOutDate)->days;

    $originalRoom = Room::findOrFail($booking->room_id);
    $originalBasePricePerNight = $originalRoom->roomClass->base_price;

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

    $roomClass = $room->roomClass;
    $basePricePerNight = $roomClass->base_price;
    
    // حساب المبلغ المدفوع
    $paidAmount = $booking->invoices->paid_amount;

    // حساب الخدمات المطلوبة
    $services = $booking->services;
    $servicesTotal = $services->sum('price');

    // حساب المبلغ الإجمالي
    $totalAmount = ($basePricePerNight * $numberOfNights) + $servicesTotal;
    $remainingAmount = $totalAmount - $paidAmount;

    $invoice = $booking->invoices;
    $lastRemainingAmount = $invoice->remaining_amount;
    $invoice->total_amount = $totalAmount;
    $invoice->remaining_amount = $remainingAmount;
    $invoice->taxes = $totalAmount * $taxRate;

    // تحقق ما إذا كان عدد الليالي أو سعر الغرفة قد زاد
    $isRoomPriceIncreased = $basePricePerNight > $originalBasePricePerNight;
    $isNumberOfNightsIncreased = $numberOfNights > $originalNumberOfNights;

    // تحديث حالة الدفع إلى 'pre_payment' إذا كانت التغييرات تتطلب دفع إضافي
    if (($isRoomPriceIncreased || $isNumberOfNightsIncreased) && $lastRemainingAmount == 0 && $remainingAmount > 0) {
        $booking->payment_status = 'pre_payment';
        $booking->save();
    }

    $invoice->save();
    $invoiceData = $invoice->only(['total_amount', 'paid_amount', 'remaining_amount']);

    if ($remainingAmount > $lastRemainingAmount) {
        return $this->returnData('Booking updated successfully. Additional payment required.', [
            'remaining_amount' => $remainingAmount,
            'invoice' => $invoiceData,
        ], 200);
    } elseif ($remainingAmount < $lastRemainingAmount) {
        return $this->returnData('Booking updated successfully. Refund required.', [
            'refund_amount' => abs($remainingAmount),
            'invoice' => $invoiceData,
        ], 200);
    } else {
        return $this->returnData('Booking updated successfully. No additional payment required.', [
            'invoice' => $invoiceData,
        ], 200);
    }
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
        $services = $booking->services;
        $username = $booking->user->first_name;
        $roomNumber = $booking->room->room_number;
        $num_adults = $booking->num_adults;
        $num_children = $booking->num_children;
        $invoiceData = [
            'id' => $invoice->id,
            'booking_id' => $invoice->booking_id,
            'paid_amount' => $invoice->paid_amount,
            'remaining_amount' => $invoice->remaining_amount,
            'total_amount' => $invoice->total_amount,
            'invoice_date' => $invoice->invoice_date,
            'taxes' => $invoice->taxes,
            'services' => $services->map(function ($service) {
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'description' => $service->description,
                    'price' => $service->price,
                    'duration' => $service->duration,
                ];
            }),
        ];

        return $this->returnData('Invoice details.', [
            'username' => $username,
            'room_number' => $roomNumber,
            'num_adults'=>$num_adults,
            'num_children' => $num_children,
            'invoice' => $invoiceData,
        ]);
    }
    

    
        public function cancelBooking(Request $request)
    {
          
        $this->validate($request, [
            'booking_id' => 'required|exists:bookings,id',
        ]);
      
        $booking = Booking::find($request->booking_id);
        if ($booking && $booking->payment_status == 'Pre_payment') {   
            $booking->room->status = 'available';
            $booking->room->save();
            $booking->payment_status = 'cancel';
            $booking->save();
          
            return $this->returnSuccessMessage('Booking canceled successfully.');
        } else {
            return $this->returnErrorMessage('Booking not found or already canceled.', 'error', 404);
        }
    }
}

