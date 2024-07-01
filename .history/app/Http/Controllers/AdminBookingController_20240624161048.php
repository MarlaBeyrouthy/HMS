<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Http\Traits\GeneralTrait;
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

    // Search for bookings based on criteria
    public function search(Request $request)
    {
        try {
            // Validate incoming request
            $validator = Validator::make($request->all(), [
                'user_id' => 'sometimes|integer|exists:users,id',
                'booking_code' => 'sometimes|string',
                // Add more validation rules as needed
            ]);

            if ($validator->fails()) {
                return $this->returnValidationErrors($validator->errors());
            }

            // Build query to search bookings
            $query = Booking::with(['user', 'room']);

            // Filter by user_id if provided
            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            // Filter by booking_code if provided
            if ($request->has('booking_code')) {
                $query->where('booking_code', 'LIKE', '%' . $request->booking_code . '%');
            }

            // Add more filters based on other criteria

            // Execute the query
            $bookings = $query->get();

            return $this->returnData('Bookings retrieved successfully', $bookings);
        } catch (\Exception $e) {
            return $this->returnError('E002', 'Failed to search bookings');
        }
    }
}
