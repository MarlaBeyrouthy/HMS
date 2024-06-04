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
        // إضافة عوامل تصفية إضافية
        if ($request->has('floor')) {
            $query->where('floor', $request->input('floor'));
        }
      
        if ($request->has('view')) {
            $query->where('view', $request->input('view'));
        }
       
    
        // تطبيق شرط إلغاء حالة الدفع
        if ($request->has('payment_status')) {
            $paymentStatus = $request->input('payment_status');
            if ($paymentStatus === 'cancel') {
                $query->whereHas('bookings', function ($query) {
                    $query->where('payment_status', 'cancel');
                });
            }
        }
    
      // استخدام eager loading لتحسين الأداء، مع تحديد الحقول المطلوبة فقط من bookings
    $rooms = $query->with(['roomClass', 'reviews', 'bookings' => function ($query) {
        $query->select('room_id', 'check_in_date', 'check_out_date');
    }])->get();

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
                $cancelledBookingsCount = 0;
                $occupied = [];
                
                // إضافة معلومات الحجوزات للغرفة
                foreach ($currentBookings as $booking) {
                    if ($booking->payment_status === 'cancel') {
                        $cancelledBookingsCount++;
                        continue;
                    }
                    $occupied[] = [
                        'start' => Carbon::parse($booking->check_in_date)->toDateTimeString(),
                        'end' => Carbon::parse($booking->check_out_date)->toDateTimeString()
                    ];
                }
                // إذا كانت جميع الحجوزات الحالية ملغاة، قم بتحديث حالة الغرفة إلى "available"
                if ($cancelledBookingsCount === $currentBookings->count()) {
                    $room->status = 'available';
                } else {
                    $room->status = 'booked';
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
