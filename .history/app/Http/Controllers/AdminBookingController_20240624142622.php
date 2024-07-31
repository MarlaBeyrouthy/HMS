namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;

class AdminBookingController extends Controller
{
    // Display a listing of the bookings
    public function index()
    {
        $bookings = Booking::with(['user', 'room'])->get();
        return view('admin.bookings.index', compact('bookings'));
    }

    // Display the specified booking
    public function show(Booking $booking)
    {
        $booking->load(['user', 'room']);
        return view('admin.bookings.show', compact('booking'));
    }
}
