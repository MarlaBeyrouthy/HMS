<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Traits\GeneralTrait; 

class RoomController extends Controller
{
    use GeneralTrait;

  
    public function searchRooms(Request $request)
    {
        $query = Room::query();
    
        // Filter by keyword if provided
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('floor', 'like', '%'.$search.'%')
                  ->orWhere('status', 'like', '%'.$search.'%')
                  ->orWhere('room_number', 'like', '%'.$search.'%');
            });
        }
    
        // Filter by price range if provided
        if ($request->has('price') && $request->has('price_comparison')) {
            $price = $request->input('price');
            $comparison = $request->input('price_comparison');
            $operator = $comparison == 'greater' ? '>' : '<';
            $query->whereHas('roomClass', function($q) use ($price, $operator) {
                $q->where('base_price', $operator, $price);
            });
        }
    
        $rooms = $query->with('roomClass')->get();
    
        return response()->json($rooms);
    }
    
    


    public function getAllRooms()
    {
        $rooms = Room::where('status', '!=', 'maintenance')->get();
        
        foreach ($rooms as $room) {
            // جلب الحجوزات الحالية
            $currentBookings = $room->bookings()->where('check_out_date', '>', now())->get();
            
            // التحقق مما إذا كانت الغرفة محجوزة حاليًا
            if ($currentBookings->isEmpty()) {
                $room->status = 'available';
            } else {
                $room->status = 'booked';
                $occupied = [];
                
                // إضافة معلومات الحجوزات للغرفة
                foreach ($currentBookings as $booking) {
                    if ($booking->payment_status === 'cancel') {
                        // إذا كانت حالة الدفع ملغاة، لا تقم بإضافة الموعد للغرفة
                        continue;
                    }
                    
                    $occupied[] = [
                        'start' => Carbon::parse($booking->check_in_date)->toDateTimeString(),
                        'end' => Carbon::parse($booking->check_out_date)->toDateTimeString()
                    ];
                }
                
                // إضافة معلومات الحجوزات المشغولة فقط إذا كانت الغرفة محجوزة
                if (!empty($occupied)) {
                    $room->occupied = $occupied;
                }
            }
        }
        
        if ($rooms->isEmpty()) {
            return $this->returnErrorMessage('No rooms found.', 'E001');
        }
        
        return $this->returnData('All rooms retrieved successfully', $rooms);
    }
    
    
    public function getRoomDetails($id)
    {
    $room = Room::with(['roomClass', 'reviews'])->find($id);
    if (!$room) {
        return $this->returnErrorMessage('No rooms found.', 'E001');
    }
    // تحضير بيانات الأوقات المحجوزة
    $occupiedTimes = [];
    foreach ($room->bookings as $booking) {
        $start = Carbon::parse($booking->check_in_date);
        $end = Carbon::parse($booking->check_out_date);
        if ($booking->payment_status === 'cancel') {
            // إذا كانت حالة الدفع ملغاة، لا تقم بإضافة الموعد للغرفة
            continue;
        }
        $occupiedTimes[] = [
            'start' => $start->toDateTimeString(),
            'end' => $end->toDateTimeString()
        ];
    }
    // التحقق مما إذا كانت العلاقة bookings موجودة قبل تجاهلها
    if ($room->relationLoaded('bookings')) {
        // تجاهل معلومات الحجوزات
        $room->unsetRelation('bookings');
    }
    // إضافة بيانات الأوقات المحجوزة إلى بيانات التفاصيل
    $roomDetails = $room->toArray();
    $roomDetails['occupied_times'] = $occupiedTimes;
    return $this->returnData('Room Details', $roomDetails);
    }
}
