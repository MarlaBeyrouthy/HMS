<?php

namespace App\Services;

use Stripe\Stripe;
use Stripe\Checkout\Session;
use App\Models\Room;
use App\Models\Booking;
use App\Models\Invoice;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BookingService
{
    public function validateBookingData($data)
    {
        $validator = Validator::make($data, [
            'room_id' => 'required|exists:rooms,id',
            'check_in_date' => 'required|date',
            'check_out_date' => 'required|date|after_or_equal:check_in_date',
            'num_adults' => 'required|integer|min:1',
            'num_children' => 'required|integer|min:0',
            'payment_method' => 'required|in:cash,stripe',
        ]);

        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }
    }

    public function checkExistingBooking($roomId, $checkInDate, $checkOutDate)
    {
        $existingBooking = Booking::where('room_id', $roomId)
            ->where(function ($query) use ($checkInDate, $checkOutDate) {
                $query->where('check_in_date', '<=', $checkOutDate)
                    ->where('check_out_date', '>=', $checkInDate);
            })->first();

        if ($existingBooking && $existingBooking->payment_status == 'cancel') {
            $existingBooking->delete();
        }

        if ($existingBooking && $existingBooking->payment_status != 'cancel') {
            throw new \Exception('There is already a booking for the selected dates.');
        }
    }

    public function createStripeSession($room, $request)
    {
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

        return $session;
    }

    public function createBooking($request, $sessionId = null)
    {
        return Booking::create([
            'user_id' => Auth::id(),
            'room_id' => $request->room_id,
            'check_in_date' => $request->check_in_date,
            'check_out_date' => $request->check_out_date,
            'num_adults' => $request->num_adults,
            'num_children' => $request->num_children,
            'payment_status' => 'Pre_payment',
            'payment_session_id' => $sessionId,
            'payment_method' => $request->payment_method,
        ]);
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

        return $invoice;
    }
}
