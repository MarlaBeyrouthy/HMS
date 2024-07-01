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
        $query = Booking::query();
         // تطبيق شروط البحث
         $searchTerm = $request->input('search');
         if ($searchTerm) {
             $query->where(function ($query) use ($searchTerm) {
                 $query->where('user_id', 'LIKE', "%$searchTerm%")
                 ->orWhere('room_id', 'LIKE', "%$searchTerm%");
        // استخدام eager loading لتحسين الأداء، مع تحديد الحقول المطلوبة فقط من bookings
        $bookings = $query->with(['roomClass', 'reviews', 'bookings' => function ($query) {
            $query->select('room_id', 'check_in_date', 'check_out_date');
        }])->get();
    )}
    };        
        // التحقق مما إذا كانت الغرف فارغة
        if ($bookings->isEmpty()) {
            return $this->returnErrorMessage('No rooms found.', 'E001');
        }

        return $this->returnData('Rooms retrieved successfully', $bookings);
    }
}
    
