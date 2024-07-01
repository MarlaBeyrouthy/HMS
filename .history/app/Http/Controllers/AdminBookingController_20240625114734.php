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
    {
        // إنشاء كائن Query Builder
        $query = Booking::query();

        // التحقق من وجود معايير البحث في الطلب وإضافتها إلى الاستعلام
        if ($request->has('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->has('room_id')) {
            $query->where('room_id', $request->input('room_id'));
        }

        if ($request->has('check_in_date')) {
            $query->whereDate('check_in_date', $request->input('check_in_date'));
        }
        if ($request->has('check_out_date')) {
            $query->whereDate('check_out_date', $request->input('check_out_date'));
        }

        $bookings = $query->get();
        if (!$bookings){
            retu
        }
        return $this->returnData('Bookings retrieved successfully', $bookings);  
        
    }
}