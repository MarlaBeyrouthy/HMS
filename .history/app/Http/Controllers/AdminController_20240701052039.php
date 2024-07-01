<?php

namespace App\Http\Controllers;

use App\Http\Traits\GeneralTrait;

use App\Models\report;
use App\Models\Room;
use App\Models\User;
use App\Services\UserService;
use Dotenv\Exception\ValidationException;
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
                'first_name', 'last_name', 'email', 'phone', 'password', 'address', 'personal_id', 'photo', 'password_confirmation', 'permission_id'
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

        return $this->returnData('User has been banned.', null, 200);
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

        $user->permission_id =1;
        $user->save();

        return $this->returnData('User has been unbanned.', null, 200);
    }

    public function deleteUser($id)
    {
        $User = User::findOrFail($id);
        if($User->permission_id==2){
            return response()->json(['message' => 'forbidden'],403);
        }
        $User->delete();

        return response()->json(['message' => 'User deleted successfully']);
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
            return response()->json(['users' => $users]);
        } else {
            // Log if no users are found
            Log::info('No Users Found');
            return response()->json(['message' => 'User not found'], 404);
        }
    }
////////////////////////////////////////////////////////////REPORTS

    public function showReports()
    {
        $reports = report::where('is_checked', 0)->get();

        return response()->json([
            'reports' => $reports,
        ]);
    }

    public function getUserReports($userId)
    {
        $reports = report::where('user_id',$userId)->get();
        if (!$reports) {
            return response()->json(['error' => 'not found'], 404);
        }
        return response()->json([
            'reports' => $reports,
        ]);
    }

    public function checkReports(Request $request)
    {
        $validatedData = $request->validate([
            'reports' => 'required|array|min:1',
            'reports.*' => 'exists:reports,id',
        ]);
        $ids=$validatedData['reports'];
        foreach ($ids as $id) {
            $report = report::find($id);
            $report->is_checked= 1;
            $report->save();
        }
        return response()->json(['message' => 'Product reports updated successfully']);
    }



///////////////////////////////////////////////////////////Room

public function deleteRoom($id)
{
    $room = Room::findOrFail($id);
    // Check if the room has bookings
    if ($room->bookings()->exists()) {
        return response()->json(['message' => 'Cannot delete room with existing bookings'], 400);
    }

    $room->delete();

    return response()->json(['message' => 'Room deleted successfully']);
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
            'photo'=> 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        // If the validation fails, return a response with errors
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }




        // Debugging - Log the photo path to verify it's correct
        // Create the room
           $room = new Room();
           $room-> floor = $request->input('floor');
           $room-> status = $request->input('status');
           $room-> room_number = $request->input('room_number');
           $room-> room_class_id = $request->input('room_class_id');
           $room-> average_rating = $request->input('average_rating', 0);


              if ($request->hasFile('photo')) {
                  $photo = $request->file('photo');
                  $photoExtension = time() . '.' . $photo->getClientOriginalExtension();
                  $photo->move(public_path('uploads/room_photo'), $photoExtension);
                  $photoPath = 'uploads/room_photo/' . $photoExtension;
                  $room->photo=$photoPath;

              }

              $room->save();

        // Return a success response
        return response()->json(['message' => 'Room created successfully', 'room' => $room], 201);
    }

    public function updateRoom(Request $request, $id)
    {
        // Find the room by ID
        $room = Room::findOrFail($id);

        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'floor' => 'nullable|in:GF,1F,2F,HP',
            'status' => 'nullable|in:available,booked,maintenance',
            'room_number' => 'nullable|string|max:255|unique:rooms,room_number,' . $room->id,
            'room_class_id' => 'nullable|exists:room_classes,id',
            'average_rating' => 'nullable|numeric|between:0,5',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Update room fields if provided
        $room->floor = $request->input('floor', $room->floor);
        $room->status = $request->input('status', $room->status);
        $room->room_number = $request->input('room_number', $room->room_number);
        $room->room_class_id = $request->input('room_class_id', $room->room_class_id);
        $room->average_rating = $request->input('average_rating', $room->average_rating);

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

        // Return the updated room
        return response()->json(['message' => 'Room updated successfully', 'room' => $room], 200);
    }



    public function indexUsers()
    {
        $users = User::all()->makeHidden('password');
        return response()->json([
            'users' => $users,
        ]);
    }

   
   
}
