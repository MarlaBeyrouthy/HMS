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

  
        public function filterRooms(Request $request)
    {
        $query = Room::query();

        // تطبيق شرط التصفيه حسب view
        if ($request->has('view')) {
            $query->where('view', $request->input('view'));
        }

        // تطبيق شرط التصفيه حسب base_price
        if ($request->has('base_price_min') || $request->has('base_price_max')) {
            $query->whereHas('roomClass', function ($query) use ($request) {
                if ($request->has('base_price_min')) {
                    $query->where('base_price', '>=', $request->input('base_price_min'));
                }
                if ($request->has('base_price_max')) {
                    $query->where('base_price', '<=', $request->input('base_price_max'));
                }
            });
        }

        // استخدام eager loading لتحسين الأداء، مع تحديد الحقول المطلوبة فقط من roomClass و reviews
        $rooms = $query->with(['roomClass', 'reviews'])->get();

        // التحقق مما إذا كانت الغرف فارغة
        if ($rooms->isEmpty()) {
            return $this->returnErrorMessage('No rooms found.', 'E001');
        }

        return $this->returnData('Rooms retrieved successfully', $rooms);
    }


    public function filterRooms(Request $request)
    {
        $query = Room::query();
        // الانضمام بين الجدولين
        $query->join('room_classes', 'rooms.room_class_id', '=', 'room_classes.id');
        if ($request->has('floor')) {
            $query->where('rooms.floor', $request->floor);
        }
        if ($request->has('status')) {
            $query->where('rooms.status', $request->status);
        }
        if ($request->has('average_rating')) {
            $query->where('rooms.average_rating', '>=', $request->average_rating);
        }
        if ($request->has('base_price')) {
            $query->where('room_classes.base_price', '<=', $request->base_price);
        }
        if ($request->has('bed_type')) {
            $query->where('room_classes.bed_type', $request->bed_type);
        }
        if ($request->has('number_of_beds')) {
            $query->where('room_classes.number_of_beds', $request->number_of_beds);
        }
        $rooms = $query->get();
        if ($rooms->isEmpty()) {
            return $this->returnErrorMessage('No rooms found.', 'E001');
        }
        return $this->returnData('Rooms retrieved successfully', $rooms);
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
