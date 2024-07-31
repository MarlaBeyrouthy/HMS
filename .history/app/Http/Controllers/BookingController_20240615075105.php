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
    
    private function validateBookingRequest($request)
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

    private function calculateInvoiceDetails($basePricePerNight, $numberOfNights)
    {
        $taxRate = 0.1;
        $totalAmount = $basePricePerNight * $numberOfNights;
        $paidAmount = $totalAmount / 4;
        $remainingAmount = $totalAmount - $paidAmount;

        return [
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
            'remaining_amount' => $remainingAmount,
            'taxes' => $totalAmount * $taxRate,
            'invoice_date' => now(),
        ];
    }

    private function createInvoice($booking, $invoiceDetails)
    {
        return Invoice::updateOrCreate(
            ['booking_id' => $booking->id],
            $invoiceDetails
        );
    }
    public function viewInvoice($bookingId)
    {
        $booking = Booking::find($bookingId);

        if (!$booking) {
            return $this->returnErrorMessage('Booking not found.', 'E006', 404);
        }

        $invoice = Invoice::where('booking_id', $bookingId)->first();

        if (!$invoice) {
            return $this->returnErrorMessage('Invoice not found for this booking.', 'E007', 404);
        }

        return $this->returnData('Invoice retrieved successfully.', [
            'invoice_id' => $invoice->id,
            'booking_id' => $invoice->booking_id,
            'total_amount' => $invoice->total_amount,
            'paid_amount' => $invoice->paid_amount,
            'remaining_amount' => $invoice->remaining_amount,
            'taxes' => $invoice->taxes,
            'service'=>$invoice->service
            'invoice_date' => $invoice->invoice_date,
        ], 200);
    }

    private function createStripeSession($bookingDetails, $paymentMethod)
    {
        Stripe::setApiKey("sk_test_51NedBNEQbJiqtI6xmfmqk6fHT6g1DmnNWTncMeoGQLVYZn8e86HvEHBQk390lhS6fEYL4DDzTjT1sCKJhV2tZpN000krSm6HlX");
        $sessionId = Str::uuid()->toString();

        $session = Session::create([
            'payment_method_types' => [$paymentMethod == 'cash' ? 'cash' : 'card'],
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => 'Booking',
                        ],
                        'unit_amount' => $bookingDetails['amount'],
                    ],
                    'quantity' => 1,
                ],
            ],
            'mode' => 'payment',
            'success_url' => $bookingDetails['success_url'] . '&session_id=' . $sessionId,
            'cancel_url' => route('booking.cancel'),
        ]);

        return [$session, $sessionId];
    }

    public function makeBooking(Request $request)
    {
        $validator = $this->validateBookingRequest($request);

        if ($validator->fails()) {
            return $this->returnErrorMessage($validator->errors()->first(), 'E001', 400);
        }

        $existingBooking = Booking::where('room_id', $request->room_id)
            ->where(function ($query) use ($request) {
                $query->where('check_in_date', '<=', $request->check_out_date)
                    ->where('check_out_date', '>=', $request->check_in_date);
            })->first();

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

        $bookingDetails = [
            'user_id' => Auth::id(),
            'room_id' => $request->room_id,
            'check_in_date' => $request->check_in_date,
            'check_out_date' => $request->check_out_date,
            'num_adults' => $request->num_adults,
            'num_children' => $request->num_children,
            'payment_status' => 'Pre_payment',
            'payment_method' => $request->payment_method,
        ];

        $roomClass = $room->roomClass;
        $numberOfNights = (new \DateTime($request->check_in_date))->diff(new \DateTime($request->check_out_date))->days;
        $invoiceDetails = $this->calculateInvoiceDetails($roomClass->base_price, $numberOfNights);

        if ($request->payment_method == 'stripe') {
            try {
                [$session, $sessionId] = $this->createStripeSession([
                    'amount' => $roomClass->base_price * 0.25 * 100,
                    'success_url' => route('booking.success') . '?room_id=' . $request->room_id . '&check_in_date=' . $request->check_in_date . '&check_out_date=' . $request->check_out_date . '&num_adults=' . $request->num_adults . '&num_children=' . $request->num_children,
                ], $request->payment_method);

                $bookingDetails['payment_session_id'] = $sessionId;

                $booking = Booking::create($bookingDetails);
                $invoice = $this->createInvoice($booking, $invoiceDetails);

                return $this->returnData('Booking created successfully.', [
                    'session_id' => $sessionId,
                    'Booking_id' => $booking->id,
                    'invoice' => $invoice,
                ], 200);
            } catch (\Exception $e) {
                return $this->returnErrorMessage($e->getMessage(), 'E003', 500);
            }
        } else {
            $booking = Booking::create($bookingDetails);
            $room->status = 'booked';
            $room->save();
            $invoice = $this->createInvoice($booking, $invoiceDetails);

            return $this->returnData('Booking created successfully.', [
                'payment_method' => 'cash',
                'Booking_id' => $booking->id,
                'invoice' => $invoice,
            ], 200);
        }
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

        $booking = Booking::find($request->booking_id);

        if (!$booking) {
            return $this->returnErrorMessage('Booking not found.', 'E006', 404);
        }

        if ($booking->payment_status !== 'Pre_payment') {
            return $this->returnErrorMessage('Payment has not been initiated yet.', 'E004', 400);
        }

        $paidAmount = $request->input('paid_amount');
        $remainingAmount = $booking->invoices->remaining_amount;

        if ($paidAmount != $remainingAmount) {
            return $this->returnErrorMessage('Paid amount does not match remaining amount.', 'E007', 400);
        }

        $prePaymentMethod = $booking->payment_method;

        if ($prePaymentMethod === 'cash' && $request->payment_method === 'stripe') {
            try {
                [$session, $sessionId] = $this->createStripeSession([
                    'amount' => $remainingAmount * 100,
                    'success_url' => route('booking.success') . '?booking_id=' . $request->booking_id,
                ], $request->payment_method);

                $booking->payment_session_id = $sessionId;
                $booking->payment_status = 'fully_paid';
                $booking->invoices->update([
                    'paid_amount' => $booking->invoices->paid_amount + $paidAmount,
                    'remaining_amount' => 0,
                ]);

                return $this->returnData('Stripe session created successfully.', [
                    'session_id' => $sessionId,
                    'username' => $booking->user->first_name,
                    'booking_id' => $booking->id,
                    'invoice' => $booking->invoices,
                ], 200);
            } catch (\Exception $e) {
                return $this->returnErrorMessage($e->getMessage(), 'E003', 500);
            }
        } elseif ($prePaymentMethod === 'stripe' && $request->payment_method === 'stripe') {
            $booking->payment_status = 'fully_paid';
            $booking->invoices->update([
                'paid_amount' => $booking->invoices->paid_amount + $paidAmount,
                'remaining_amount' => 0,
            ]);

            return $this->returnData('Payment completed successfully.', [
                'username' => $booking->user->first_name,
                'booking_id' => $booking->id,
                'invoice' => $booking->invoices,
            ], 200);
        } else {
            return $this->returnErrorMessage('Invalid payment method.', 'E008', 400);
        }
    }

    public function cancelBooking($id)
    {
        $booking = Booking::find($id);

        if (!$booking) {
            return $this->returnErrorMessage('Booking not found.', 'E006', 404);
        }

        $booking->payment_status = 'cancel';
        $booking->save();

        return $this->returnSuccessMessage('Booking canceled successfully.', 'S001');
    }
}
