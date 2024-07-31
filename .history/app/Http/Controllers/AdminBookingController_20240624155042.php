<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Http\Traits\GeneralTrait;

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

    // Display the specified booking
    public function show(Booking $booking)
    {
        try {
            if (!$booking) {
                return $this->returnError('E404', 'Booking not found', 404);
            }

            $booking->load(['user', 'room']);
            return $this->returnData('Booking retrieved successfully', $booking);
        } catch (\Exception $e) {
            return $this->returnError('E002', 'Failed to retrieve booking');
        }
    }
}
