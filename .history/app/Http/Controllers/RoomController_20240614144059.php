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
        if ($request) {
            $query->where('floor', $request);
        }if ($request) {
            $query->where('status', $request);
        }if ($request) {
            $query->where('room_number', $request);
        }if ($request) {
            $query->where('average_rating', $request);
        }
        if ($request->has('floor')) {
            $query->where('floor', $request->input('floor'));
        }  if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }  if ($request->has('room_number')) {
            $query->where('room_number', $request->input('view'));
        }  
        if ($request->has('view')) {
            $query->where('view', $request->input('view'));
        }
    
        if ($request->has('base_price')) {
            $priceComparison = $request->input('price_comparison', 'less_than');
            $basePrice = $request->input('base_price');
    
            if ($priceComparison === 'less_than') {
                $query->whereHas('roomClass', function ($query) use ($basePrice) {
                    $query->where('base_price', '<=', $basePrice);
                });
            } elseif ($priceComparison === 'greater_than') {
                $query->whereHas('roomClass', function ($query) use ($basePrice) {
                    $query->where('base_price', '>=', $basePrice);
                });
            }
        }
    
        $rooms = $query->with(['roomClass', 'reviews', 'bookings' => function ($query) {
            $query->select('room_id', 'check_in_date', 'check_out_date');
        }])->get();
    
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
