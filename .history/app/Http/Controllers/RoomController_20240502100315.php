<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Room;
use Illuminate\Http\Request;
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
    
        // الحصول على النتائج وإرجاعها
        $rooms = $query->get();
        return $this->returnData('Rooms retrieved successfully', $rooms);
    }


    public static function filterRooms($filters)
{
    $query = self::query();

    if(isset($filters['floor'])) {
        $query->where('floor', $filters['floor']);
    }

    if(isset($filters['status'])) {
        $query->where('status', $filters['status']);
    }

    if(isset($filters['room_number'])) {
        $query->where('room_number', $filters['room_number']);
    }

    if(isset($filters['class_name'])) {
        $query->whereHas('roomClass', function ($query) use ($filters) {
            $query->where('class_name', $filters['class_name']);
        });
    }

    // يمكنك إضافة شروط تصفية إضافية حسب الحاجة هنا

    return $query->get();
}

    

    // الحصول على كل الغرف مع تفاصيلها والأوقات المتاحة للحجز
    public function getAllRooms()
    {
        $rooms = Room::all();
        $data = [];

        foreach ($rooms as $room) {
            $roomData = [
                'floor' => $room->floor,
                'status' => $room->status,
                'room_number' => $room->room_number,
                'room_class_id' => $room->room_class_id,
                'average_rating' => $room->average_rating,
               // 'available_times' => [],
            ];

            // حساب الأوقات المتاحة إذا كانت هناك حجوزات
            if ($room->bookings->count() > 0) {
               // $availableTimes = $this->calculateAvailableTimes($room->id);
             //   $roomData['available_times'] = $availableTimes;
            }

            $data[] = $roomData;
        }

        return $this->returnData('Rooms retrieved successfully', $data);
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
