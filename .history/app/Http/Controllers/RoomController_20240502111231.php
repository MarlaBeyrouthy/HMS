<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Traits\GeneralTrait; // استخدام ميزة عامة

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

        $rooms = $query->get();
        if ($rooms->isEmpty()) {
            return $this->returnErrorMessage('No rooms found.', 'E001', Response::HTTP_NOT_FOUND);
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
            return $this->returnErrorMessage('No rooms found.', 'E001', Response::HTTP_NOT_FOUND);
        }
        return $this->returnData('Rooms retrieved successfully', $rooms);
    }





 
    public function getAllRooms()
    {
        $rooms = Room::all();
        // حساب أوقات التوافر والانشغال لكل غرفة
        foreach ($rooms as $room) {
            $bookings = $room->bookings()->where('end_time', '>', now())->get();
            $availability = [];
            $occupied = [];
            foreach ($bookings as $booking) {
                $start = Carbon::parse($booking->start_time);
                $end = Carbon::parse($booking->end_time);
    
                $availability[] = [
                    'start' => $start->toDateTimeString(),
                    'end' => $end->toDateTimeString()
                ];
    
                $occupied[] = [
                    'start' => $start->toDateTimeString(),
                    'end' => $end->toDateTimeString()
                ];
            }
    
            // إضافة أوقات التوافر والانشغال لكل غرفة كـ attributes
            $room->availability = $availability;
            $room->occupied = $occupied;
        }
    
        // التحقق مما إذا كان هناك غرف متاحة
        if ($rooms->isEmpty()) {
            return $this->returnErrorMessage('No rooms found.', 'E001', Response::HTTP_NOT_FOUND);
        }
    
        // إرجاع البيانات في حال وجود غرف متاحة
        return $this->returnData('All rooms retrieved successfully', $rooms);
    }
    





    // حساب الأوقات المتاحة للحجز لغرفة معينة
    private function calculateAvailableTimes($roomId)
    {
        // جلب الغرفة
        $room = Room::findOrFail($roomId);
        $bookings = $room->bookings->sortBy('check_in_date');
    
        // تحضير قائمة للأوقات المتاحة
        $availableTimes = [];
        //تحويل تواريخ الحجز من صيغة نصية إلى كائنات Carbon التي تسمح بإجراء عمليات متقدمة على التواريخ
        $endOfDay = Carbon::parse('23:59:59');
        $lastBookingEndTime = Carbon::parse('00:00:00');
        $availableAllDay = true;
    
        // حساب الأوقات المتاحة بناءً على الحجوزات
        foreach ($bookings as $booking) {
            $bookingStart = Carbon::parse($booking->check_in_date);
            $bookingEnd = Carbon::parse($booking->check_out_date);
    
            if ($bookingStart->gt($lastBookingEndTime)) {
                $availableTimes[] = [
                    'start' => $lastBookingEndTime,
                    'end' => $bookingStart,
                ];
                $availableAllDay = false;
            }
    
            $lastBookingEndTime = $bookingEnd->gt($lastBookingEndTime) ? $bookingEnd : $lastBookingEndTime;
        }
    
        if ($lastBookingEndTime->lt($endOfDay)) {
            $availableTimes[] = [
                'start' => $lastBookingEndTime,
                'end' => $endOfDay,
            ];
            $availableAllDay = false;
        }
    
        if (empty($bookings)) {
            // لا توجد حجوزات - جميع الأوقات متاحة
            $availableTimes[] = [
                'start' => Carbon::parse('00:00:00'),
                'end' => Carbon::parse('23:59:59'),
            ];
            return $this->returnData('Available Times', $availableTimes);
        } else {
            if ($availableAllDay) {
                return $this->returnData('Available Times', [['start' => Carbon::parse('00:00:00'), 'end' => Carbon::parse('23:59:59')]]);
            } else {
                return $this->returnData('Available Times (except during bookings)', $availableTimes);
            }
        }
    }
    // الحصول على تفاصيل غرفة معينة مع الأوقات المتاحة للحجز
    public function getRoomDetails($roomId)
    {
        $room = Room::getRoomDetails($roomId);

        if ($room) {
            $availableTimes = $this->calculateAvailableTimes($roomId);
            $room->availableTimes = $availableTimes;
            return $this->returnData('Success', $room);
        } else {
            return $this->returnErrorMessage('Room Not Found');
        }
    }
}
