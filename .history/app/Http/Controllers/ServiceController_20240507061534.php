<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ServiceController extends Controller
{
    /public function addServicesToBooking(Request $request, $bookingId)
    {
        // Retrieve the booking
        $booking = Booking::find($bookingId);

        if (!$booking) {
            return response()->json(['message' => 'Booking not found'], 404);
        }

        // Validate the request data
        $validatedData = $request->validate([
            'service_ids' => 'required|array',
            'service_ids.*' => 'exists:services,id',
            'quantities' => 'required|array',
            'quantities.*' => 'numeric|min:1',
        ]);

        // Attach services to the booking
        $booking->addServicesToBooking($validatedData['service_ids'], $validatedData['quantities']);

        return response()->json(['message' => 'Services added successfully'], 200);
    }
}
}
