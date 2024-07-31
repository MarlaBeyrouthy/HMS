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
    public function searchBookings(Request $request)
    {
        $query = Booking::query();
    
        // تطبيق شروط البحث
        $searchTerm = $request->input('search');
        if ($searchTerm) {
            $query->where(function ($query) use ($searchTerm) {
                $query->where('payment_status', 'LIKE', "%$searchTerm%")
                    ->orWhere('payment_method', 'LIKE', "%$searchTerm%")
                    ->orWhereHas('user', function ($query) use ($searchTerm) {
                        $query->where('name', 'LIKE', "%$searchTerm%");
                    })
                    ->orWhereHas('room', function ($query) use ($searchTerm) {
                        $query->where('room_number', 'LIKE', "%$searchTerm%");
                    });
            });
        }
    
        if ($request->has('check_in_date')) {
            $query->whereDate('check_in_date', $request->input('check_in_date'));
        }
    
        if ($request->has('check_out_date')) {
            $query->whereDate('check_out_date', $request->input('check_out_date'));
        }
    
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->input('payment_status'));
        }
    
        if ($request->has('payment_method')) {
            $query->where('payment_method', $request->input('payment_method'));
        }
    
        // استخدام eager loading لتحسين الأداء، مع تحديد الحقول المطلوبة فقط من العلاقات
        $bookings = $query->with(['user', 'room'])->get();
    
        // التحقق مما إذا كانت الحجوزات فارغة
        if ($bookings->isEmpty()) {
            return $this->returnErrorMessage('No bookings found.', 'E001');
        }else
    
        return $this->returnData('Bookings retrieved successfully', $bookings);
    }
    
}
