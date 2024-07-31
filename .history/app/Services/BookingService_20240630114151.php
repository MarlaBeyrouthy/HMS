<?php

namespace App\Services;

use App\Http\Traits\GeneralTrait;
use Stripe\Stripe;
use App\Models\Room;
use App\Models\Booking;
use App\Models\Invoice;
use Illuminate\Support\Str;
use Stripe\Checkout\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class BookingService
{
    use GeneralTrait;
    public function validateBookingRequest($request)
    {
        $validator = Validator::make($request->all(), [
            'room_id' => 'required|exists:rooms,id',
            'check_in_date' => 'required|date',
            'check_out_date' => 'required|date|after_or_equal:check_in_date',
            'num_adults' => 'required|integer|min:1',
            'num_children' => 'required|integer|min:0',
            'payment_method' => 'required|in:cash,stripe',
        ]);

        return $validator;
    }

    public function checkExistingBooking($request)
    {
        return Booking::where('room_id', $request->room_id)
            ->where(function ($query) use ($request) {
                $query->where('check_in_date', '<=', $request->check_out_date)
                    ->where('check_out_date', '>=', $request->check_in_date);
            })->first();
    }

    public function handleStripePayment($request, $room)
    {
        Stripe::setApiKey("sk_test_51NedBNEQbJiqtI6xmfmqk6fHT6g1DmnNWTncMeoGQLVYZn8e86HvEHBQk390lhS6fEYL4DDzTjT1sCKJhV2tZpN000krSm6HlX");

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
                        'unit_amount' => $room->roomClass->base_price * 0.25 * 100,
                    ],
                    'quantity' => 1,
                ],
            ],
            'mode' => 'payment',
            'success_url' => route('booking.success') . '?room_id=' . $request->room_id . '&check_in_date=' . $request->check_in_date . '&check_out_date=' . $request->check_out_date . '&num_adults=' . $request->num_adults . '&num_children=' . $request->num_children . '&session_id=' . $sessionId,
            'cancel_url' => route('booking.cancel'),
        ]);

        return [
            'sessionId' => $sessionId,
            'session' => $session
        ];
    }

    public function createBooking($request, $paymentMethod, $paymentStatus, $sessionId = null)
    {
        return Booking::create([
            'user_id' => Auth::id(),
            'room_id' => $request->room_id,
            'check_in_date' => $request->check_in_date,
            'check_out_date' => $request->check_out_date,
            'num_adults' => $request->num_adults,
            'num_children' => $request->num_children,
            'payment_status' => $paymentStatus,
            'payment_session_id' => $sessionId,
            'payment_method' => $paymentMethod,
        ]);
    }

    public function checkIfUserIsBanned($user)
    {
        if ($user->permission_id == 4) {
            throw new \Exception('You are banned from making bookings.', 403);
        }
    }
    public function createInvoice($booking, $room)
    {
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

        return $invoice->only(['taxes', 'total_amount', 'paid_amount', 'remaining_amount']);
    }

    public function cancelBooking($booking)
    {
        if ($booking->payment_status == 'Pre_payment') {
            $booking->room->status = 'available';
            $booking->room->save();
            $booking->payment_status = 'cancel';
            $booking->save();
            return true;
        }
        return false;
    }
    public function viewInvoice($bookingId, $userId)
    {
        $booking = Booking::find($bookingId);

        if (!$booking) {
            return ['error' => 'Booking not found.', 'code' => 404];
        }

        if ($booking->user_id !== $userId) {
            return ['error' => 'Unauthorized access.', 'code' => 403];
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

        return [
            'username' => $username,
            'room_number' => $roomNumber,
            'num_adults' => $num_adults,
            'num_children' => $num_children,
            'invoice' => $invoiceData,
        ];
    }
    public function validateUpdateBookingRequest($data)
    {
        $validator = Validator::make($data, [
            'booking_id' => 'required|exists:bookings,id',
            'room_id' => 'required|exists:rooms,id',
            'check_in_date' => 'required|date',
            'check_out_date' => 'required|date|after_or_equal:check_in_date',
            'num_adults' => 'required|integer|min:1',
            'num_children' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return ['error' => $validator->errors()->first(), 'code' => 400];
        }
        return ['success' => true];
    }
    public function updateInvoiceBasedOnPaymentStatus($booking)
{
    // الحصول على الفاتورة المرتبطة بالحجز
    $invoice = Invoice::where('booking_id', $booking->id)->first();

    if (!$invoice) {
        return $this->returnErrorMessage('Invoice not found for this booking', 'I404');
    }

    // تحديث الفاتورة بناءً على حالة الدفع الجديدة
    switch ($booking->payment_status) {
        case 'fully_paid':
            $invoice->paid_amount = $invoice->total_amount;
            $invoice->remaining_amount = 0;
            break;
        case 'Pre_payment':
            // هنا يمكنك تحديد المبلغ المدفوع المسبق مثلاً 25%
            $paidAmount = $invoice->total_amount * 0.25;
            $invoice->paid_amount = $paidAmount;
            $invoice->remaining_amount = $invoice->total_amount - $paidAmount;
            break;
        case 'not_paid':
            $invoice->paid_amount = 0;
            $invoice->remaining_amount = $invoice->total_amount;
            break;
        case 'cancel':
            $invoice->paid_amount = 0;
            $invoice->remaining_amount = 0;
            break;
    }

    // حفظ التغييرات على الفاتورة
    $invoice->save();

    return $invoice->only(['total_amount', 'paid_amount', 'remaining_amount', 'taxes']);
}


    public function checkAuthorization($booking)
    {
        if ($booking->user_id !== Auth::id()) {
            return ['error' => 'Unauthorized access.', 'code' => 403];
        }
        return ['success' => true];
    }

    public function checkRoomStatus($room)
    {
        if ($room->status == 'maintenance') {
            return ['error' => 'The room is currently unavailable for booking.', 'code' => 400];
        }
        return ['success' => true];
    }

    public function updateBookingData($booking, $data)
    {
        // حفظ البيانات الأصلية للحجز
        $originalCheckInDate = new \DateTime($booking->check_in_date);
        $originalCheckOutDate = new \DateTime($booking->check_out_date);
        $originalNumberOfNights = $originalCheckInDate->diff($originalCheckOutDate)->days;
        
        $originalRoom = Room::findOrFail($booking->room_id);
        $originalBasePricePerNight = $originalRoom->roomClass->base_price;
        
        // تحديث بيانات الحجز
        $booking->room_id = $data['room_id'];
        $booking->check_in_date = $data['check_in_date'];
        $booking->check_out_date = $data['check_out_date'];
        $booking->num_adults = $data['num_adults'];
        $booking->num_children = $data['num_children'];
        $booking->save();
        
        // حساب البيانات المحدثة
        $checkInDate = new \DateTime($booking->check_in_date);
        $checkOutDate = new \DateTime($booking->check_out_date);
        $numberOfNights = $checkInDate->diff($checkOutDate)->days;
        
        $taxRate = 0.1;
        
        $room = Room::findOrFail($data['room_id']);
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
        
        // تحديث الفاتورة
        $invoice = $booking->invoices;
        $lastRemainingAmount = $invoice->remaining_amount;
        $invoice->total_amount = $totalAmount;
        $invoice->remaining_amount = $remainingAmount;
        $invoice->taxes = $totalAmount * $taxRate;
        $invoice->save();
        
        $invoiceData = $invoice->only(['total_amount', 'paid_amount', 'remaining_amount']);
        
        // التحقق إذا كان هناك زيادة في سعر الغرفة أو عدد الليالي
        $isRoomPriceIncreased = $basePricePerNight > $originalBasePricePerNight;
        $isNumberOfNightsIncreased = $numberOfNights > $originalNumberOfNights;
        
        // تحديث حالة الدفع إلى 'pre_payment' إذا كانت التغييرات تتطلب دفع إضافي
        if (($isRoomPriceIncreased || $isNumberOfNightsIncreased) && $lastRemainingAmount == 0 && $remainingAmount > 0) {
            $booking->payment_status = 'pre_payment';
            $booking->save();
        }
        
        // تحديد رسالة الرد بناءً على المبلغ المتبقي
        if ($remainingAmount > $lastRemainingAmount) {
            return [
                'message' => 'تم تحديث الحجز بنجاح. دفعة إضافية مطلوبة.',
                'data' => [
                    'remaining_amount' => $remainingAmount,
                    'invoice' => $invoiceData,
                ],
                'code' => 200
            ];
        } elseif ($remainingAmount < $lastRemainingAmount) {
            return [
                'message' => 'تم تحديث الحجز بنجاح. استرداد مبلغ مطلوب.',
                'data' => [
                    'refund_amount' => abs($remainingAmount),
                    'invoice' => $invoiceData,
                ],
                'code' => 200
            ];
        } else {
            return [
                'message' => 'تم تحديث الحجز بنجاح. لا دفعات إضافية مطلوبة.',
                'data' => [
                    'invoice' => $invoiceData,
                ],
                'code' => 200
            ];
        }
    }

}
