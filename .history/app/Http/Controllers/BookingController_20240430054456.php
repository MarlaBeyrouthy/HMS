<?php

namespace App\Http\Controllers;

use Stripe\Stripe;
use App\Models\Room;
use App\Models\Booking;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Stripe\Checkout\Session;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    

    
    public function handlePaymentSuccess(Request $request)
    {
        $booking = Booking::where('payment_session_id', $request->session_id)->first();
        if (!$booking) 
        {
            return response()->json(['error' => 'Booking not found.'], 404);
        }
        $booking->payment_status = 'Pre_payment';
        $booking->save();
        $taxRate = 0.1;
        $room = $booking->room;
        $room->status = 'booked';
        $room->save();
        $remainingAmount = $booking->room->roomClass->base_price - ($booking->room->roomClass->base_price * 0.25);
        
        $invoice = Invoice::firstOrCreate
        (
            ['booking_id' => $booking->id],
            [
                'total_amount' => $booking->room->roomClass->base_price + ($booking->room->roomClass->base_price * $taxRate),
                'paid_amount' => $booking->room->roomClass->base_price * 0.25,
                'remaining_amount' => $remainingAmount,
                'taxes' => $booking->room->roomClass->base_price * $taxRate,
                'invoice_date' => now(),
            ]
        );
        $username = $booking->user->first_name;
        $roomNumber = $booking->room->room_number;
        return response()->json([
            'username' => $username,
            'room_number' => $roomNumber,
            'message' => 'Payment successful.',
            'invoice' => $invoice
        ]);
    }



    public function completePayment(Request $request)
    {
        $booking = Booking::where('id', $request->booking_id)->first();
        if ($booking && $booking->payment_status == 'Pre_payment') 
        {
            $remainingAmount = $booking->room->roomClass->base_price - ($booking->room->roomClass->base_price * 0.25);
            $booking->payment_status = 'fully_paid';
            $booking->save();
            $invoice = $booking->invoice;
            if ($invoice) 
            {
                $invoice->paid_amount = $invoice->total_amount;
                $invoice->remaining_amount = 0;
                $invoice->invoice_date = now();
                $invoice->save();
            } else 
            {
                $taxRate = 0.1;
                $totalAmount = $booking->room->roomClass->base_price + ($booking->room->roomClass->base_price * $taxRate);
                Invoice::create
                ([
                    'booking_id' => $booking->id,
                    'total_amount' => $totalAmount,
                    'paid_amount' => $totalAmount,
                    'remaining_amount' => 0,
                    'taxes' => $totalAmount * $taxRate,
                    'invoice_date' => now(),
                ]);
            }
            $username = $booking->user->first_name;
            $roomNumber = $booking->room->room_number;
            return response()->json
            ([
                'username' => $username,
                'room_number' => $roomNumber,
                'message' => 'Payment completed successfully.',
                'invoice' => $invoice
            ]);
        } 
        else 
        {
            return response()->json(['error' => 'Booking not found or payment not completed.'], 404);
        }
    }
    
    
    
    public function cancelBooking(Request $request)
        {
            $booking = Booking::find($request->booking_id);
            if ($booking && $booking->payment_status == 'Pre_payment') 
            {
                $booking->room->status = 'available';
                $booking->room->save();
                $booking->payment_status = 'cancel';
                $booking->save();
                // $booking->delete();
                return response()->json(['message' => 'Booking canceled successfully.']);

            }
            else 
            {
                return response()->json(['error' => 'Booking not found or already cancel.'], 404);
            }
        }
    
}