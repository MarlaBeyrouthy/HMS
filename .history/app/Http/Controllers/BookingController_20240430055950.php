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

class BookingController extends Controller
{
    use GeneralTrait;
    
    
    public function handlePaymentSuccess(Request $request)
    {
        $booking = Booking::where('payment_session_id', $request->session_id)->first();
        if (!$booking) {
            return $this->returnErrorMessage('Booking not found.', 'error', 404);
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
        return $this->returnData('Payment successful.', 
        [
            'username' => $username,
            'room_number' => $roomNumber,
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
            return $this->returnData('Payment completed successfully.', 
            [
                'username' => $username,
                'room_number' => $roomNumber,
                'invoice' => $invoice
            ]);
        } 
        else 
        {
            return $this->returnErrorMessage('Booking not found or payment not completed.', 'error', 404);    
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
                return $this->returnSuccessMessage('Booking canceled successfully.');
                } else 
                {
                    return $this->returnErrorMessage('Booking not found or already canceled.', 'error', 404);
                }
        }
    
}