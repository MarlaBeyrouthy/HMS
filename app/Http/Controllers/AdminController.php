<?php

namespace App\Http\Controllers;

use App\Http\Traits\GeneralTrait;

use App\Models\Booking;
use App\Models\report;
use App\Models\Room;
use App\Models\User;
use App\Services\UserService;
//use Barryvdh\DomPDF\Facade\Pdf;
//use Barryvdh\DomPDF\Pdf;

use Barryvdh\DomPDF\Facade as PDF;

//use Barryvdh\DomPDF\PDF;
use Dotenv\Exception\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\RoomClass;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    use GeneralTrait;

    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

////////////////////////////////////////////////ADMIN PROFILE
    public function login(Request $request)
    {
        try {
            $userData = $request->only( [
                'password',
                'email'
            ] );
            $token = $this->userService->login($userData);
            return $this->returnData('you login  Successfully.', $token, 200);
        } catch (\Exception $e) {
            return $this->returnError($e->getMessage(), 'Registration Error');

        }
    }

    public function myProfile()
    {
        $user_data = auth()->user();
        return $this->returnData('User data.', $user_data, 200);
    }

    public function updateProfile(Request $request)
    {
        try {
            $user = auth()->user();
            $updatedUser = $this->userService->updateProfile($user, $request->all());
            return $this->returnData('Your profile has been updated.', $updatedUser, 200);
        } catch (ValidationException $e) {
            return $this->returnError($e->getMessage(), 'Validation Error');
        } catch (\Exception $e) {
            return $this->returnError($e->getMessage(), 400);
        }
    }

    /////////////////////////////////////////////////////// MANAGE USERS

    public function getProfile($user_id)
    {
        try {
            $user = $this->userService->getProfile($user_id);
            return $this->returnData('User profile data', $user, 200);
        } catch (\Exception $e) {
            return $this->returnError('User Not Found', 'User Not Found', 404);
        }
    }

    public function createUser(Request $request)
    {
        try {
            // Validate email first using the service method
            $this->userService->validateEmail($request);

            $userData = $request->only([
                'first_name', 'last_name', 'email', 'phone', 'password', 'address', 'personal_id',
                'photo', 'password_confirmation', 'permission_id'
            ]);
            $token = $this->userService->registerUser($userData, true);
            return $this->returnData('User Created Successfully.', $token, 200);
        } catch (ValidationException $e) {
            return $this->returnError($e->getMessage(), 'Validation Error');
        } catch (\Exception $e) {
            return $this->returnError($e->getMessage(), 'Creation Error');
        }
    }

    public function BanUser($userId)
    {
        $user = User::find($userId);

        if (!$user) {
            return $this->returnError('User not found', 'User Not Found', 404);
        }

        $user->permission_id =4;
        $user->save();

        return $this->returnSuccessMessage('User has been banned.', 'S000', 200);
    }

    /*
      if ($user->permission_id =4) {
        return response()->json(['message' => 'You are banned from making reservations.'], 403);
    }
     */
    public function unBanUser($userId)
    {
        $user = User::find($userId);

        if (!$user) {
            return $this->returnError('User not found', 'User Not Found', 404);
        }

        $user->permission_id = 1;
        $user->save();

        return $this->returnSuccessMessage('User has been unbanned.', 'S000', 200);
    }

    public function deleteUser($id)
    {
        $User = User::findOrFail($id);
        if ($User->permission_id == 2) {
            return $this->returnErrorMessage('forbidden', 'S403', 403);
        }
        $User->delete();

        return $this->returnSuccessMessage('User deleted successfully', 'S000', 200);
    }

    public function searchUsers(Request $request)
    {
        // Retrieve the search input
        $query = $request->input('search');
        Log::info('Search Query Input: ' . $query);

        // Construct the query
        $users = User::where('first_name', 'like', "%$query%")
            ->orWhere('email', 'like', "%$query%")
            ->get();

        // Log the raw SQL query and the bindings
        Log::info('Executed Query: ' . User::where('first_name', 'like', "%$query%")->orWhere('email', 'like', "%$query%")->toSql());
        Log::info('Query Bindings: ' . json_encode(User::where('first_name', 'like', "%$query%")->orWhere('email', 'like', "%$query%")->getBindings()));

        // Log the number of users found
        Log::info('Users Found: ' . $users->count());

        if ($users->isNotEmpty()) {
            // Log the user data
            Log::info('User Data: ', $users->toArray());
            return $this->returnData('Users Found', $users, 200);
        } else {
            // Log if no users are found
            Log::info('No Users Found');
            return $this->returnErrorMessage('User not found', 'S404', 404);
        }
    }
////////////////////////////////////////////////////////////REPORTS

    public function showReports()
    {
        $reports = report::where('is_checked', 0)->get();
        return $this->returnData('Reports data.', $reports, 200);
    }

    public function getUserReports($userId)
    {
        $reports = report::where('user_id', $userId)->get();
        if ($reports->isEmpty()) {
            return $this->returnErrorMessage('Reports not found', 'S404', 404);
        }
        return $this->returnData('User reports data.', $reports, 200);
    }

    public function checkReports(Request $request)
    {
        $validatedData = $request->validate([
            'reports' => 'required|array|min:1',
            'reports.*' => 'exists:reports,id',
        ]);
        $ids = $validatedData['reports'];
        foreach ($ids as $id) {
            $report = report::find($id);
            $report->is_checked = 1;
            $report->save();
        }
        return $this->returnSuccessMessage('Product reports updated successfully', 'S000', 200);
    }




    public function downloadInvoice($id) {
        try {
            // Fetch the booking and related invoice
            $booking = Booking::with( [ 'user', 'room.roomClass', 'invoices' ] )->findOrFail( $id );

            // Calculate the number of days
            $checkInDate  = new \DateTime( $booking->check_in_date );
            $checkOutDate = new \DateTime( $booking->check_out_date );
            $interval     = $checkInDate->diff( $checkOutDate )  ;
            $numDays = $interval->days + 1; // Add one day to include the check-out day

            // Get additional data for the invoice
            $invoice   = $booking->invoices;
            $user      = $booking->user;
            $room      = $booking->room;
            $roomClass = $room->roomClass;

            // Pass data to the view
            $data = [
                'booking'   => $booking,
                'invoice'   => $invoice,
                'user'      => $user,
                'room'      => $room,
                'roomClass' => $roomClass,
                'numDays'   => $numDays,
                'checkInDate' => $booking->check_in_date,
                'checkOutDate' => $booking->check_out_date,
            ];

            // Create a PDF and load the view
            $pdf = app( 'dompdf.wrapper' );
            $pdf->loadView( 'pdf', $data );

            // Download the generated PDF
            return $pdf->download( 'invoice.pdf' );
        } catch ( ModelNotFoundException $e ) {
            return response()->json( [
                'message' => 'Booking not found',
            ], 404 );
        } catch ( \Exception $e ) {
            return response()->json( [
                'message' => 'An error occurred while generating the invoice',
                'error'   => $e->getMessage(),
            ], 500 );
        }
    }
///////////////////////////////////////////////////////////Room

    public function deleteRoom($id)
    {
        $room = Room::findOrFail($id);
        // Check if the room has bookings

        if ($room->bookings()->exists()) {
            return $this->returnErrorMessage('Cannot delete room with existing bookings', 'S400', 400);
        }

        $room->delete();
        return $this->returnSuccessMessage('Room deleted successfully', 'S000', 200);
    }

    public function createRoom(Request $request)
    {
        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'floor' => 'required|in:GF,1F,2F,HP',
            'status' => 'required|in:available,booked,maintenance',
            'room_number' => 'required|string|max:255|unique:rooms,room_number',
            'room_class_id' => 'required|exists:room_classes,id',
            'average_rating' => 'nullable|numeric|between:0,5',
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'view' => 'required|string|max:255'
                ]);

        if ($validator->fails()) {
            return $this->returnErrorMessage('Validation failed', 'S422', 422);
        }

        $room = new Room();
        $room->floor = $request->input('floor');
        $room->status = $request->input('status');
        $room->room_number = $request->input('room_number');
        $room->room_class_id = $request->input('room_class_id');
        $room->average_rating = $request->input('average_rating', 0);
        $room->view = $request->input('view');
        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            $photoExtension = time() . '.' . $photo->getClientOriginalExtension();
            $photo->move(public_path('uploads/room_photo'), $photoExtension);
            $photoPath = 'uploads/room_photo/' . $photoExtension;
            $room->photo = $photoPath;
        }

        $room->save();
        return $this->returnData('Room created successfully', $room, 201);
    }

    public function updateRoom(Request $request, $id)
    {
        $room = Room::findOrFail($id);

        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'floor' => 'nullable|in:GF,1F,2F,HP',
            'status' => 'nullable|in:available,booked,maintenance',
            'room_number' => 'nullable|string|max:255|unique:rooms,room_number,' . $room->id,
            'room_class_id' => 'nullable|exists:room_classes,id',
            'average_rating' => 'nullable|numeric|between:0,5',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'view' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return $this->returnErrorMessage('Validation failed', 'S422', 422);
        }

        // Update room fields if provided
        $room->floor = $request->input('floor', $room->floor);
        $room->status = $request->input('status', $room->status);
        $room->room_number = $request->input('room_number', $room->room_number);
        $room->room_class_id = $request->input('room_class_id', $room->room_class_id);
        $room->average_rating = $request->input('average_rating', $room->average_rating);
        $room->view = $request->input('view',$room->view);

        // Handle the photo upload
        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');

            // Delete the old photo if it exists
            if ($room->photo && file_exists(public_path($room->photo))) {
                unlink(public_path($room->photo));
            }

            // Save the new photo
            $photoExtension = time() . '.' . $photo->getClientOriginalExtension();
            $photo->move(public_path('uploads/room_photo'), $photoExtension);
            $room->photo = 'uploads/room_photo/' . $photoExtension;
        }

        // Delete the photo if requested
        if (isset($request['delete_photo'])) {
            if ($room->photo && file_exists(public_path($room->photo))) {
                unlink(public_path($room->photo));
            }
            $room->photo = null;
        }

        // Save the updated room
        $room->save();
        return $this->returnData('Room updated successfully', $room, 200);
    }

    public function indexUsers()
    {
        $users = User::all()->makeHidden('password');
        return $this->returnData('Users data.', $users, 200);
    }
}


