public function requestService(Request $request)
{
    $bookingId = $request->input('booking_id');
    $serviceId = $request->input('service_id');
    $quantity = $request->input('quantity');

    // Find the booking and service
    $booking = Booking::find($bookingId);
    $service = Service::find($serviceId);

    if (!$booking || !$service) {
        return $this->returnError('Booking or Service not found', 404);
    }

    // Check if the authenticated user is the owner of the booking
    $user = auth()->user();
    if ($booking->user_id !== $user->id) {
        return $this->returnError('Unauthorized', 401);
    }

    // Calculate total price for the service
    $totalPrice = $service->price * $quantity;

    // Attach the service to the booking
    $booking->bookingServices()->create([
        'service_id' => $serviceId,
        'quantity' => $quantity,
        'total_price' => $totalPrice
    ]);

    // Update the invoice
    $invoice = $booking->invoice;
    if ($invoice) {
        $invoice->total_amount += $totalPrice;
        $invoice->remaining_amount += $totalPrice;
        $services = json_decode($invoice->services);
        $services[] = $service->service_name;
        $invoice->services = json_encode($services);
        $invoice->save();
    } else {
        Invoice::create([
            'booking_id' => $bookingId,
            'paid_amount' => 0,
            'remaining_amount' => $totalPrice,
            'total_amount' => $totalPrice,
            'invoice_date' => now(),
            'services' => json_encode([$service->service_name])
        ]);
    }

    return $this->returnSuccessMessage('Service requested successfully');
}