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

    // Display the specified bookingpublic function show($id) // تعديل لاستخدام $id بدلاً من Booking $booking
    public function search(Request $request)
    {
        try {
            // Initialize query builder for Booking model
            $query = Booking::with(['user', 'room']);
    
            // Check if ID is provided
            if ($request->has('id')) {
                $query->where('id', $request->id);
            }
    
            // Check if other conditions are provided (example: user_id, room_id, etc.)
            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }
    
            // Add more conditions as needed...
    
            // Perform the query to fetch the booking
            $booking = $query->firstOrFail();
    
            return $this->returnData('Booking retrieved successfully', $booking);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->returnError('E404', 'Booking not found', 404);
        } catch (\Exception $e) {
            return $this->returnError('E002', 'Failed to retrieve booking');
        }
    }
    
}
