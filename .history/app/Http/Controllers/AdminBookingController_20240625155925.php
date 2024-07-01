<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Traits\GeneralTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;

class AdminBookingController extends Controller
{
    use GeneralTrait;

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
            $booking = Booking::with(['user', 'room'])->findOrFail($id);
            return $this->returnData('Booking details retrieved successfully', $booking);
        } catch (\Exception $e) {
            return $this->returnError('E002', 'Failed to retrieve booking details');
        }
    }
    
    public function updateBooking(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'room_id' => 'required|exists:rooms,id',
            'check_in_date' => 'required|date',
            'check_out_date' => 'required|date|after_or_equal:check_in_date',
            'num_adults' => 'required|integer|min:1',
            'num_children' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return $this->returnErrorMessage($validator->errors()->first(), 'E001', 400);
        }

        try {
            $booking = Booking::findOrFail($id);

            $room = Room::findOrFail($request->room_id);
            if ($room->status == 'maintenance') {
                return $this->returnErrorMessage('The room is currently unavailable for booking.', 'E002', 400);
            }

            $booking->room_id = $request->room_id;
            $booking->check_in_date = $request->check_in_date;
            $booking->check_out_date = $request->check_out_date;
            $booking->num_adults = $request->num_adults;
            $booking->num_children = $request->num_children;
            $booking->save();

            $checkInDate = new \DateTime($booking->check_in_date);
            $checkOutDate = new \DateTime($booking->check_out_date);
            $numberOfNights = $checkInDate->diff($checkOutDate)->days;

            $taxRate = 0.1;

            $roomClass = $room->roomClass;
            $basePricePerNight = $roomClass->base_price;

            $totalBaseAmount = $basePricePerNight * $numberOfNights;
            $taxes = $totalBaseAmount * $taxRate;
            $totalAmountWithTaxes = $totalBaseAmount + $taxes;

            $invoice = $booking->invoices;
            $invoice->total_amount = $totalAmountWithTaxes;
            $invoice->taxes = $taxes;
            $invoice->save();

            return $this->returnData('Booking updated successfully.', $booking, 200);
        } catch (\Exception $e) {
            return $this->returnErrorMessage('Failed to update booking.', 'E003', 500);
        }
    }


}