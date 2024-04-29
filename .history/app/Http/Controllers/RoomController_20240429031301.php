<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Room;
use App\Http\Traits\GeneralTrait;
use Carbon\Carbon;

class RoomController extends Controller
{
    use GeneralTrait;

    public function searchRooms(Request $request)
    {
        $floor = $request->input('floor');
        $status = $request->input('status');
        $room_number = $request->input('room_number');
        $room_class_id = $request->input('room_class_id');
        $average_rating = $request->input('average_rating');
        $query = Room::query();
        if ($floor) 
        {
            $query->where('floor', $floor);
        }
        if ($status) 
        {
            $query->where('status', $status);
        }
        if ($room_number) 
        {
            $query->where('room_number', $room_number);
        }
        if ($room_class_id) 
        {
            $query->where('room_class_id', $room_class_id);
        }
        if ($average_rating) 
        {
            $query->where('average_rating', $average_rating);
        }
        $rooms = $query->get();
        return $this->returnData( 'Rooms retrieved successfully',$rooms);
    }


    public function getAllRooms()
    {
        $rooms = Room::all();
        $data = [];
        foreach ($rooms as $room) 
        {
            $roomData =
            [
                'floor' => $room->floor,
                'status' => $room->status,
                'room_number' => $room->room_number,
                'room_class_id' => $room->room_class_id,
                'average_rating' => $room->average_rating,
                'available_times' => [],
            ];
            if ($room->bookings->count() > 0) 
            {
                $availableTimes = $this->calculateAvailableTimes($room->id);
                $roomData['available_times'] = $availableTimes;
            }
            $data[] = $roomData;
        }
        return $this->returnData('Room retrieved successfully', $data);
    }
        
private function calculateAvailableTimes($roomId)
    {
        $room = Room::findOrFail($roomId);
        $bookings = $room->bookings->sortBy('check_in_date');

        $availableTimes = [];
        $endOfDay = Carbon::parse('23:59:59');

        $lastBookingEndTime = Carbon::parse('00:00:00');

        // حساب الأوقات المتاحة بين الحجوزات
        foreach ($bookings as $booking) {
            $bookingStart = Carbon::parse($booking->check_in_date);
            $bookingEnd = Carbon::parse($booking->check_out_date);

            // إذا كانت هناك فترة متاحة بين الحجز السابق والحالي
            if ($bookingStart->gt($lastBookingEndTime)) {
                $availableTimes[] = [
                    'start' => $lastBookingEndTime,
                    'end' => $bookingStart,
                ];
            }

            $lastBookingEndTime = $bookingEnd->gt($lastBookingEndTime) ? $bookingEnd : $lastBookingEndTime;
        }

        // إذا كانت هناك فترة متاحة بعد الحجز الأخير
        if ($lastBookingEndTime->lt($endOfDay)) {
            $availableTimes[] = [
                'start' => $lastBookingEndTime,
                'end' => $endOfDay,
            ];
        }

        return $availableTimes;

    }

        public function getRoomDetails($roomId)
        {
            $room = Room::getRoomDetails($roomId);
            if ($room) 
            {
                $availableTimes = $this->calculateAvailableTimes($roomId);
                $room->availableTimes = $availableTimes;
                return response()->json(['success' => true, 'data' => $room]);
            } else {
                return response()->json(['success' => false, 'message' => 'Room not found'], 404);
            }
        }






}
