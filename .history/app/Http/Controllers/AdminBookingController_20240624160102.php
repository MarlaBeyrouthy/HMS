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

    // Search for bookings with filters
    public function search(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'nullable|exists:users,id',
            'room_id' => 'nullable|exists:rooms,id',
            'check_in_date' => 'nullable|date',
            'check_out_date' => 'nullable|date',
            'payment_status' => 'nullable|in:Pre_payment,Full_payment,Cancel',
            'payment_method' => 'nullable|in:cash,stripe',
        ]);

        if ($validator->fails()) {
            return $this->returnError('E002', $validator->errors()->first());
        }

        try {
            $query = Booking::with(['user', 'room']);

            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            if ($request->has('room_id')) {
                $query->where('room_id', $request->room_id);
            }

            if ($request->has('check_in_date')) {
                $query->whereDate('check_in_date', $request->check_in_date);
            }

            if ($request->has('check_out_date')) {
                $query->whereDate('check_out_date', $request->check_out_date);
            }

            if ($request->has('payment_status')) {
                $query->where('payment_status', $request->payment_status);
            }

            if ($request->has('payment_method')) {
                $query->where('payment_method', $request->payment_method);
            }

            $bookings = $query->get();

            return $this->returnData('Bookings retrieved successfully', $bookings);
        } catch (\Exception $e) {
            return $this->returnError('E003', 'Failed to search bookings');
        }
    }
}
