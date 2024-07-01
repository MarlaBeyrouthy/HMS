<?php

namespace App\Http\Controllers;

use Log;
use App\Models\Room;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Services\BookingService;
use App\Http\Traits\GeneralTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;

class AdminBookingController extends Controller
{
    use GeneralTrait;

    protected $bookingService;

    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }
    // Display a listing of the bookings
    public function index()
    {
       
        try {
            $bookings = Booking::with(['user', 'room'])->get();
            
            return $this->returnData('Bookings retrieved successfully', $bookings);
        } catch (\Exception $e) {
            return $this->returnError('E001', 'Failed to retrieve bookings');
        }
    }

    public function searchBookings(Request $request)
    {

        $query = Booking::query();
        if ($request->has('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }if ($request->has('room_id')) {
            $query->where('room_id', $request->input('room_id'));
        }if ($request->has('check_in_date')) {
            $query->where('check_in_date', '>=', $request->input('check_in_date'));
        }if ($request->has('check_out_date')) {
            $query->where('check_out_date', '<=', $request->input('check_out_date'));
        }if ($request->has('payment_status')) {
            $query->where('payment_status', $request->input('payment_status'));
        }if ($request->has('payment_method')) {
            $query->where('payment_method', $request->input('payment_method'));
        }
        $bookings = $query->get();
        if ($bookings->isEmpty()) {
            return $this->returnErrorMessage('No bookings found', 'B404');
        }return $this->returnData('Bookings retrieved successfully', $bookings);  
    }

    public function destroy($id)
    {
        try {
            $booking = Booking::findOrFail($id);
            $booking->delete();
            return $this->returnSuccessMessage('Booking deleted successfully');
        } catch (\Exception $e) {
            return $this->returnError('E006', 'Failed to delete booking');
        }
    }
    public function showDetails($id)
    {
        try {
            $booking = Booking::with(['user', 'room','invoices'])->findOrFail($id);
            return $this->returnData('Booking details retrieved successfully', $booking);
        } catch (\Exception $e) {
            return $this->returnError('E002', 'Failed to retrieve booking details');
        }
    }

    public function updatePaymentStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'payment_status' => 'required|in:fully_paid,Pre_payment,not_paid,cancel',
        ]);
        if ($validator->fails()) {
            return $this->returnErrorMessage($validator->errors()->first(), 'E001', 400);
        }
        $booking = Booking::find($id);
        if (!$booking) {
            return $this->returnErrorMessage('Booking not found', 'B404');
        }
        $booking->payment_status = $request->input('payment_status');
        $booking->save();
        $invoiceData = $this->updateInvoiceBasedOnPaymentStatus($booking);
        $existingBooking = $this->bookingService->checkExistingBooking($request);

        return $this->returnData('Payment status updated successfully', $booking);
    }

    public function createBooking(Request $request)
    {
        // Validate the request
        $validator = $this->bookingService->validateBookingRequest($request);
        if ($validator->fails()) {
            return $this->returnErrorMessage($validator->errors()->first(), 'E001', 400);
        }

        // Check for existing bookings
        $existingBooking = $this->bookingService->checkExistingBooking($request);
        if ($existingBooking) {
            return $this->returnErrorMessage('The selected room is already booked for the specified dates.', 'E002', 400);
        }

        // Determine payment method and status
        $paymentMethod = $request->input('payment_method');
        $paymentStatus = $paymentMethod === 'stripe' ? 'Pre_payment' : 'not_paid';

        // Handle Stripe payment if needed
        $sessionId = null;
        if ($paymentMethod === 'stripe') {
            $room = Room::findOrFail($request->input('room_id'));
            $stripeResponse = $this->bookingService->handleStripePayment($request, $room);
            $sessionId = $stripeResponse['sessionId'];
        }

        // Create the booking
        $booking = $this->bookingService->createBooking($request, $paymentMethod, $paymentStatus, $sessionId);

        // Create the invoice
        $invoiceData = $this->bookingService->createInvoice($booking, Room::findOrFail($request->input('room_id')));

        return $this->returnData('Booking created successfully.', [
            'booking' => $booking,
            'invoice' => $invoiceData
        ], 201);
    }   
   
}   