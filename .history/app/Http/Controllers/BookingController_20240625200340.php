<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\Booking;
use Illuminate\Http\Request;
use App\Services\BookingService;
use App\Http\Traits\GeneralTrait;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    use GeneralTrait;

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
                $session = $this->bookingService->createStripeSession($request, $room);
                $booking = $this->bookingService->createBooking($request, $session->id);
                $invoice = $this->bookingService->calculateInvoice($booking);
                $invoiceData = $invoice->only(['taxes', 'total_amount', 'paid_amount', 'remaining_amount']);
                return $this->returnData('Booking created successfully.', [
                    'payment_method' => $request->payment_method,
                    'session_id' => $session->id,
                    'Booking_id' => $booking->id,
                    'invoice' => $invoiceData,
                ], 200);
            } catch (\Exception $e) {
                return $this->returnErrorMessage($e->getMessage(), 'E003', 500);
            }
        } else {
            $booking = $this->bookingService->createBooking($request);
            $room->status = 'booked';
            $room->save();
            $invoice = $this->bookingService->calculateInvoice($booking);
            $invoiceData = $invoice->only(['taxes', 'total_amount', 'paid_amount', 'remaining_amount']);
            return $this->returnData('Booking created successfully.', [
                'payment_method' => 'cash',
                'Booking_id' => $booking->id,
                'invoice' => $invoiceData,
            ], 200);
        }
    }

    public function cancelBooking(Request $request)
    {
          
        $this->validate($request, [
            'booking_id' => 'required|exists:bookings,id',
        ]);
        $booking = Booking::find($request->booking_id);
        if ($booking->user_id !== auth()->id()) {
            return $this->returnErrorMessage('Unauthorized access.', 'error', 403);
            }
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


    public function completePayment(Request $request)
    {
        // Assuming a session_id is provided in the request to complete payment
        $sessionId = $request->input('session_id');
        $booking = $this->bookingService->findBookingBySessionId($sessionId);

        if (!$booking) {
            return $this->returnErrorMessage('Booking not found or session expired.', 'E005', 404);
        }

        $this->bookingService->completeBookingPayment($booking);

        return $this->returnSuccessMessage('Payment completed successfully.');
    }

    public function viewInvoice($bookingId)
    {
        $invoice = $this->bookingService->getInvoiceByBookingId($bookingId);

        if (!$invoice) {
            return $this->returnErrorMessage('Invoice not found.', 'E007', 404);
        }

        return $this->returnData('Invoice retrieved successfully.', $invoice);
    }

    public function updateBooking(Request $request, $id)
    {
        $booking = $this->bookingService->findBookingById($id);

        if (!$booking) {
            return $this->returnErrorMessage('Booking not found.', 'E005', 404);
        }

        if ($booking->user_id != Auth::id()) {
            return $this->returnErrorMessage('Unauthorized access.', 'E006', 403);
        }

        $validator = $this->bookingService->validateBookingRequest($request);

        if ($validator->fails()) {
            return $this->returnErrorMessage($validator->errors()->first(), 'E001', 400);
        }

        $updatedBooking = $this->bookingService->updateBooking($request, $booking);

        return $this->returnSuccessMessage('Booking updated successfully.', $updatedBooking);
    }

    public function showBookingDetails($id)
    {
        $booking = $this->bookingService->findBookingById($id);

        if (!$booking) {
            return $this->returnErrorMessage('Booking not found.', 'E005', 404);
        }

        if ($booking->user_id != Auth::id()) {
            return $this->returnErrorMessage('Unauthorized access.', 'E006', 403);
        }

        return $this->returnData('Booking details retrieved successfully.', $booking);
    }

    public function getUserBookings()
    {
        $user = Auth::user();
        $bookings = $this->bookingService->getUserBookings($user->id);

        if ($bookings->isEmpty()) {
            return $this->returnErrorMessage('No bookings found.', 'E008', 404);
        }

        return $this->returnData('User bookings retrieved successfully.', $bookings);
    }
}
