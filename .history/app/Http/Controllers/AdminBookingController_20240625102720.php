<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Http\Traits\GeneralTrait;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Builder;

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

    public function searchBookings(Request $request)
{
    ilder $query) use ($request) {
                $query->where('room_class_id', $request->room_class_id);
            });
        }

        // Add more filters based on other criteria

        // Execute the query
        $bookings = $query->get();

        return $this->returnData('Bookings retrieved successfully', $bookings);
    } catch (\Exception $e) {
        return $this->returnError('E002', 'Failed to search bookings');
    }
}

}
