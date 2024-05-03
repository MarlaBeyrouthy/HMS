<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Room;
use Illuminate\Http\Request;
use App\Http\Traits\GeneralTrait; // استخدام ميزة عامة

class RoomController extends Controller
{
    use GeneralTrait; // استخدام ميزة عامة

    // عملية البحث عن الغرف بناءً على معايير محددة
    public function searchRooms(Request $request)
    {
        $floor = $request->input('floor');
        $status = $request->input('status');
        $roomNumber = $request->input('room_number');
        $roomClassId = $request->input('room_class_id');
        $averageRating = $request->input('average_rating');

        $query = Room::query();

        // تطبيق شروط البحث
        if ($floor) {
            $query->where('floor', $floor);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($roomNumber) {
            $query->where('room_number', $roomNumber);
        }

        if ($roomClassId) {
            $query->where('room_class_id', $roomClassId);
        }

        if ($averageRating) {
            $query->where('average_rating', $averageRating);
        }

        // الحصول على النتائج وإرجاعها
        $rooms = $query->get();
        return $this->returnData('Rooms retrieved successfully', $rooms);
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
                'available_times' => [],
            ];

            // حساب الأوقات المتاحة إذا كانت هناك حجوزات
            if ($room->bookings->count() > 0) {
                $availableTimes = $this->calculateAvailableTimes($room->id);
                $roomData['available_times'] = $availableTimes;
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
