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
        }
        $bookings = $query->get();
        if ($bookings->isEmpty()) {
            return $this->returnErrorMessage('No bookings found', 'B404');
        }
        return $this->returnData('Bookings retrieved successfully', $bookings);  
        
    }

    public function store(Request $request)
    {
    $validator = Validator::make($request->all(), [
        'user_id' => 'required|exists:users,id',
        'room_id' => 'required|exists:rooms,id',
        'check_in_date' => 'required|date',
        'check_out_date' => 'required|date|after:check_in_date',
        'num_adults' => 'required|integer|min:1',
        'num_children' => 'nullable|integer|min:0',
        'payment_method' => 'required|string',
        'payment_status' => 'required|string',
        ]);

    if ($validator->fails()) {
        return $this->returnErrorMessage($validator->errors(), 'E002', Response::HTTP_BAD_REQUEST);
        }

    try {
        $booking = Booking::create($request->all());
        return $this->returnData('Booking created successfully', $booking, Response::HTTP_CREATED);
        } catch (\Exception $e) {
        return $this->returnError('E003', 'Failed to create booking');
    }
}

}