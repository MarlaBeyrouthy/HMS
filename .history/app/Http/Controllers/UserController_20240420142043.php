<?php

namespace App\Http\Controllers;

use App\Mail\AccountConfirmationMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Http\Tr

class UserController extends Controller
{

    public function sendVerificationCode(Request $request)
    {
        // Validate the input data
        $this->validate($request, [
            'email' => 'required|email|unique:users,email'
        ]);

        $code=\random_int(100000,999999);

        // Store the email and verification code in the session
        $request->session()->put('email', $request->input('email'));
        $request->session()->put('verification_code',$code );

        Mail::to($request->input('email'))->send(new AccountConfirmationMail);

        return response()->json([
            "message"=>"A verification code has been sent to your email."], 200);

    }

    public function verifyCode(Request $request)
    {
        // Validate the input data
        $this->validate($request, [
            'verification_code' => 'required|digits:6'
        ]);

        // Retrieve the email and verification code from the session
        $email = $request->session()->get('email');
        $code = $request->session()->get('verification_code');

        // Check if the verification code submitted by the user is valid
        if ($request->input('verification_code') == $code) {
            // Store the email in the session again
            $request->session()->put('email', $email);
            return $this.
            return response()->json(['message' => 'Email address verified successfully.'], 200);
        } else {
            return response()->json(['error' => 'Invalid verification code.'], 400);
        }
    }

    public function registerUser(Request $request) {
        // Validate the input data
        $this->validate( $request, [
            'first_name'     => 'required',
            'last_name'     => 'required',
            'phone'    => 'required',
           // 'permission_id'=> 'required|in:1,3', // Only allow permission IDs 1 or 3
            'password' => 'required|string|min:8|confirmed',
            "address"=>"required",
            "personal_id"=>"required"

        ]);

        // Retrieve the email from the session
        $email = $request->session()->get('email');

        // Create a new user record in the database

        $user = new User();
        $user->first_name=$request->first_name;
        $user->last_name=$request->last_name;

        $user->address=$request->address;
        $user->photo=$request->photo;
        $user->email = $email;
        $user->password=bcrypt($request->password);
        $user->phone=$request->phone;
        $user->personal_id=$request->personal_id;

            // $user->permission_id =$request->permission_id;



        $photo = $request->file('photo');
        if ($request->hasFile('photo')) {
            $photoExtension = time() . '.' . $photo->getClientOriginalExtension();
            $photo->move(public_path('uploads/users_photo'), $photoExtension);
            $photoPath = 'uploads/users_photo/' . $photoExtension;
            $user->photo = $photoPath;
        }


        $user->save();
        $token=$user->createToken("auth_token")->accessToken;


        // Clear the session data
        $request->session()->forget('email');

        return response()->json([
            'message' => 'User registered successfully.',
            "access_token"=>$token,
        ], 200);
    }

    public function login(Request $request)
    {
        //validation
        $login_data=$request->validate([
            "email"=>"required",
            "password"=>"required",
        ]);

        //validate author data
        if (!auth()->attempt($login_data)  ){
            return response()->json([
                "status"=>false,
                "message"=>"invalid contents "

            ]);
        }

        $token=auth()->user()->createToken("auth_token")->accessToken;
        //send response
        return \response()->json([
            "status"=>true,
            "message"=>"user logged in successfully",
            "access_token"=>$token
        ]);

    }

    public function myProfile()
    {
        $user_data = auth()->user()->makeHidden(['verification_code', 'verified']);
        return response()->json([
            "status" => true,
            "message" => "user data",
            "data" => $user_data
        ]);
    }

    public function getProfile($user_id)
    {
        $user = User::find($user_id);

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ], 404);
        }
        else {
            $user->makeHidden(['verification_code', 'verified','email']);
        }

        return response()->json([
            'status' => true,
            'message' => 'User profile data',
            'data' => $user
        ]);
    }

    //logout-get
    public function logout()
    {

        auth()->user()->token()->delete();

        return \response()->json([
            "status"=>true,
            "message"=>"user log out successfully"
        ]);

    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        // Validate the input
        $validatedData = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            //  'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable',
            'photo' => 'nullable|image|max:2048',
            'current_password' => 'required_with:new_password',
            'new_password' => 'nullable|string|min:8|confirmed',
        ]);

        // Update the user's profile information
        $user->first_name = $validatedData['first_name'];
        $user->last_name = $validatedData['last_name'];
        $user->phone = $validatedData['phone']?? $user->phone;
        // $user->email = $validatedData['email'];
        $user->address = $validatedData['address']?? $user->address;

        $photo = $request->file('photo');
        if ($request->hasFile('photo')) {
            $photoExtension = time() . '.' . $photo->getClientOriginalExtension();
            $photo->move(public_path('uploads/users_photo'), $photoExtension);
            $photoPath = 'uploads/users_photo/' . $photoExtension;
            $user->photo = $photoPath;
        }



        // Update the user's password if a new password has been provided
        if (isset($validatedData['new_password'])) {
            $currentPassword = $validatedData['current_password'];
            $newPassword = $validatedData['new_password'];

            // Check if the current password is correct
            if (Hash::check($currentPassword, $user->password)) {
                // Hash and save the new password
                $user->password = Hash::make($newPassword);
            } else {
                // If the current password is incorrect, redirect with an error message
                return response()->json([
                    "message"=>'The current password is incorrect.'
                ],400);
            }
        }

        // Save the updated profile information and password
        $user->save();

        // Redirect the user to the profile page with a success message
        return response()->json([
            "message"=>'Your profile has been updated.'
        ],200);

    }

    public function checkPassword(Request $request)
    {
        $inputPassword  = $request->input( 'password' );
        $hashedPassword = auth()->user()->password;

        if ( Hash::check( $inputPassword, $hashedPassword ) ) {
            return response()->json( [
                'message' => 'Valid password'
            ] );
        } else {
            return response()->json( [
                'message' => 'Invalid password'
            ] );
        }
    }


}
