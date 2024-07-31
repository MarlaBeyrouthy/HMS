<?php
namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use App\Http\Traits\GeneralTrait;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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

    // Display the specified booking
    public function show($id) // تعديل لاستخدام $id بدلاً من Booking $booking
    {
        try {
            $booking = Booking::with(['user', 'room'])->findOrFail($id); // استخدام findOrFail للبحث عن الحجز

            return $this->returnData('Booking retrieved successfully', $booking);
        } catch (ModelNotFoundException $e) {
            return $this->returnError('E404', 'Booking not found', 404);
        } catch (\Exception $e) {
            return $this->returnError('E002', 'Failed to retrieve booking');
        }
    }
}
