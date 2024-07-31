<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Http\Traits\GeneralTrait;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Builder;

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
    $query = B::query();
        
    // تطبيق شروط البحث
    $searchTerm = $request->input('search');
    if ($searchTerm) {
        $query->where(function ($query) use ($searchTerm) {
            $query->where('floor', 'LIKE', "%$searchTerm%")
                ->orWhere('status', 'LIKE', "%$searchTerm%")
                ->orWhere('room_number', 'LIKE', "%$searchTerm%")
                ->orWhereHas('roomClass', function ($query) use ($searchTerm) {
                    $query->where('class_name', 'LIKE', "%$searchTerm%");
                })
                ->orWhereHas('reviews', function ($query) use ($searchTerm) {
                    $query->where('comment', 'LIKE', "%$searchTerm%");
                });
        });
    }

}
