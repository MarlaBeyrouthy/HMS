<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;
use App\Services\BookingService;
use Illuminate\Support\Facades\Auth;
use League\Event\GeneratorTrait;

class BookingController extends Controller
{
    use GeneratorTrait;
    protected $bookingService;

    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    public function makeBooking(Request $request)
    {
        $user = Auth::user();
        if ($user->permission_id == 4) {
            return $this->returnErrorMessage('You are banned from making bookings.', 'E004', 403);
        }

        $validator = $this->bookingService->validateBookingRequest($request);

        if ($validator->fails()) {
            return $this->returnErrorMessage($validator->errors()->first(), 'E001', 400);
        }

        $existingBooking = $this->bookingService->checkExistingBooking($request);

        if ($existingBooking && $existingBooking->payment_status == 'cancel') {
            $existingBooking->delete();
        }

        if ($existingBooking && $existingBooking->payment_status != 'cancel') {
            return $this->returnErrorMessage('There is already a booking for the selected dates.', 'E001', 400);
        }

        $room = Room::findOrFail($request->room_id);

        if ($room->status == 'maintenance') {
            return $this->returnErrorMessage('The room is currently unavailable for booking.', 'E002', 400);
        }

        if ($request->payment_method == 'stripe') {
            try {
                $paymentDetails = $this->bookingService->handleStripePayment($request, $room);
                $sessionId = $paymentDetails['sessionId'];
                $paymentMethod = $request->payment_method;
                $booking = $this->bookingService->createBooking($request, $paymentMethod, 'Pre_payment', $sessionId);
                $invoiceData = $this->bookingService->createInvoice($booking, $room);

                return $this->returnData('Booking created successfully.', [
                    'payment_method' => $paymentMethod,
                    'session_id' => $sessionId,
                    'Booking_id' => $booking->id,
                    'invoice' => $invoiceData,
                ], 200);
            } catch (\Exception $e) {
                return $this->returnErrorMessage($e->getMessage(), 'E003', 500);
            }
        } else {
            $booking = $this->bookingService->createBooking($request, 'cash', 'Pre_payment');
            $room->status = 'booked';
            $room->save();

            $invoiceData = $this->bookingService->createInvoice($booking, $room);

            return $this->returnData('Booking created successfully.', [
                'payment_method' => 'cash',
                'Booking_id' => $booking->id,
                'invoice' => $invoiceData,
            ], 200);
        }
    }
}
