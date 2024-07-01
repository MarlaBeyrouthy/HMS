<?php

namespace App\Http\Controllers;

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
        }if ($request->has('payment_status')) {
            $query->where('payment_status', $request->input('payment_status'));
        }if ($request->has('payment_status')) {
            $query->where('payment_status', $request->input('payment_status'));
        }

        payment_method
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
    
    

}