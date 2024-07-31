<?php

namespace App\Services;

use Stripe\Stripe;
use App\Models\Room;
use App\Models\Booking;
use App\Models\Invoice;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Stripe\Checkout\Session;

class BookingService
{
    public function validateBookingRequest($request)
    {
        return Validator::make($request->all(), [
            'room_id' => 'required|exists:rooms,id',
            'check_in_date' => 'required|date',
            'check_out_date' => 'required|date|after_or_equal:check_in_date',
            'num_adults' => 'required|integer|min:1',
            'num_children' => 'required|integer|min:0',
            'payment_method' => 'required|in:cash,stripe',
        ]);
    }

    public function checkExistingBooking($request)
    {
        return Booking::where('room_id', $request->room_id)
            ->where(function ($query) use ($request) {
                $query->where('check_in_date', '<=', $request->check_out_date)
                    ->where('check_out_date', '>=', $request->check_in_date);
            })->first();
    }

    public function createStripeSession($request, $room)
    {
        Stripe::setApiKey("sk_test_51NedBNEQbJiqtI6xmfmqk6fHT6g1DmnNWTncMeoGQLVYZn8e86HvEHBQk390lhS6fEYL4DDzTjT1sCKJhV2tZpN000krSm6HlX");

        $sessionId = Str::uuid()->toString();
        $paymentMethod = $request->payment_method;
        return Session::create([
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

    public function calculateInvoice($booking)
    {
        $checkInDate = new \DateTime($booking->check_in_date);
        $checkOutDate = new \DateTime($booking->check_out_date);
        $numberOfNights = $checkInDate->diff($checkOutDate)->days;

        $taxRate = 0.1;
        $room = Room::find($booking->room_id);
        $basePricePerNight = $room->roomClass->base_price;

        $totalBaseAmount = $basePricePerNight * $numberOfNights;
        $taxes = $totalBaseAmount * $taxRate;
        $paidAmount = $totalBaseAmount / 4;
        $remainingAmount = $totalBaseAmount - $paidAmount + $taxes;
        $totalAmountWithTaxes = $totalBaseAmount + $taxes;

        return Invoice::updateOrCreate(
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

    public function findBookingById($id)
    {
        return Booking::find($id);
    }

    public function findBookingBySessionId($sessionId)
    {
        return Booking::where('payment_session_id', $sessionId)->first();
    }

    public function completeBookingPayment($booking)
    {
        $booking->update(['payment_status' => 'completed']);
        $booking->room->update(['status' => 'booked']);
    }

    public function getInvoiceByBookingId($bookingId)
    {
        return Invoice::where('booking_id', $bookingId)->first();
    }

    public function updateBooking($request, $booking)
    {
        $booking->update([
            'check_in_date' => $request->check_in_date,
            'check_out_date' => $request->check_out_date,
            'num_adults' => $request->num_adults,
            'num_children' => $request->num_children,
        ]);

        $this->calculateInvoice($booking);

        return $booking;
    }

    public function getUserBookings($userId)
    {
        return Booking::where('user_id', $userId)->get();
    }
}
