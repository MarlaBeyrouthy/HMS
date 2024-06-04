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
