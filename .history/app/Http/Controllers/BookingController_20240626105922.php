<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\Booking;
use GuzzleHttp\ClientTrait;
use Illuminate\Http\Request;
use App\Services\BookingService;
use App\Http\Traits\GeneralTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

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

    public function completePayment(Request $request)
    {
        if (!Auth::check()) {
            return $this->returnErrorMessage('User not authenticated.', 'E001', 401);
        }
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|exists:bookings,id',
            'paid_amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,stripe',
        ]);
       // $validator = $this->bookingService->validateBookingRequest($request);

        if ($validator->fails()) {
            return $this->returnErrorMessage('Invalid input data.', 'E005', 400);
        }

        $booking = Booking::find($request->booking_id);

        if (!$booking) {
            return $this->returnErrorMessage('Booking not found.', 'E006', 404);
        }

        if ($booking->user_id !== Auth::id()) {
            return $this->returnErrorMessage('Unauthorized access.', 'E008', 403);
        }

        if ($booking->payment_status !== 'Pre_payment') {
            return $this->returnErrorMessage('Payment has not been initiated yet.', 'E004', 400);
        }

        $paidAmount = $request->input('paid_amount');
        $paymentMethod = $request->input('payment_method');
        $remainingAmount = $booking->invoices->remaining_amount;

        if ($paidAmount != $remainingAmount) {
            return $this->returnErrorMessage('Paid amount does not match remaining amount.', 'E007', 400);
        }

        $prePaymentMethod = $booking->payment_method;

        if ($prePaymentMethod === 'cash' && $paymentMethod === 'stripe') {
            try {
                $stripeResponse = $this->bookingService->handleStripePayment($request, $booking->room);
                $sessionId = $stripeResponse['sessionId'];
                $session = $stripeResponse['session'];

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
            $booking->invoices->paid_amount += $paidAmount;
            $booking->invoices->remaining_amount -= $paidAmount;
            $booking->payment_status = 'fully_paid';
            $booking->invoices->save();
            $booking->save();

            return $this->returnData('Continue with existing Stripe session.', [
                'session_id' => $booking->payment_session_id,
                'username' => $booking->user->first_name,
                'booking_id' => $booking->id,
                'invoice' => $booking->invoices,
            ], 200);
        } else {
            $booking->invoices->paid_amount += $paidAmount;
            $booking->invoices->remaining_amount -= $paidAmount;
            $booking->payment_status = 'fully_paid';
            $booking->save();
            $booking->invoices->save();

            return $this->returnData('Payment completed successfully.', [
                'payment_status' => $booking->payment_status,
                'username' => $booking->user->first_name,
                'room_number' => $booking->room->room_number,
                'invoice' => $booking->invoices,
            ], 200);
        }
    }

    public function cancelBooking(Request $request)
    {
        $this->validate($request, [
            'booking_id' => 'required|exists:bookings,id',
        ]);

        $booking = Booking::find($request->booking_id);

        if ($booking && $this->bookingService->cancelBooking($booking)) {
            return $this->returnSuccessMessage('Booking canceled successfully.');
        } else {
            return $this->returnErrorMessage('Booking not found or already canceled.', 'error', 404);
        }
    }

    public function viewInvoice(Request $request)
    {
        $this->validate($request, [
            'id' => 'required|exists:bookings,id',
        ]);

        $invoiceDetails = $this->bookingService->viewInvoice($request->id, Auth::id());

        if (isset($invoiceDetails['error'])) {
            return $this->returnErrorMessage($invoiceDetails['error'], 'error', $invoiceDetails['code']);
        }

        return $this->returnData('Invoice details.', $invoiceDetails);
    }

    public function updateBooking(Request $request)
    {
        // التحقق من صحة البيانات
        $validationResult = $this->bookingService->validateUpdateBookingRequest($request->all());
        if (isset($validationResult['error'])) {
            return $this->returnErrorMessage($validationResult['error'], 'E001', $validationResult['code']);
        }

        // جلب الحجز
        $booking = Booking::find($request->booking_id);

        // التحقق من الأذونات
        $authorizationResult = $this->bookingService->checkAuthorization($booking);
        if (isset($authorizationResult['error'])) {
            return $this->returnErrorMessage($authorizationResult['error'], 'E008', $authorizationResult['code']);
        }

        // التحقق من حالة الغرفة
        $room = Room::findOrFail($request->room_id);
        $roomStatusResult = $this->bookingService->checkRoomStatus($room);
        if (isset($roomStatusResult['error'])) {
            return $this->returnErrorMessage($roomStatusResult['error'], 'E002', $roomStatusResult['code']);
        }

        // تحديث بيانات الحجز والفاتورة
        $updateResult = $this->bookingService->updateBookingData($booking, $request->all());
        return $this->returnData($updateResult['message'], $updateResult['data'], $updateResult['code']);
    }

    public function getUserBookings()
{
    $user = Auth::user();
    $bookings = Booking::with('room', 'invoices')
                       ->where('user_id', $user->id)
                       ->get();

    if ($bookings->isEmpty()) {
        return ['error' => 'No bookings found for the current user.', 'code' => 404];
    }

    return ['data' => $bookings, 'code' => 200];
}
public function showBookingDetails($id)
{
    $booking = Booking::with('room', 'user', 'invoices')->find($id);

    
    if (!$booking) {
        return $this->returnErrorMessage('Booking not found.', 'E006', 404);
    }
    if ($booking->user_id !== Auth::id()) {
        return $this->returnErrorMessage('Unauthorized access.', 'E008', 403);
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

}
