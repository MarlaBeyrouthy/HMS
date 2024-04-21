<?php

namespace App\Http\Controllers;

use App\Mail\AccountConfirmationMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Http\Traits\GeneralTrait;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    use GeneralTrait;
    public function sendVerificationCode(Request $request)
    {
        try {
            // Validate the input data
            $this->validate($request, [
                'email' => 'required|email|unique:users,email'
            ]);
    
            $code = random_int(100000, 999999);
    
            // Store the email and verification code in the session
            $request->session()->put('email', $request->input('email'));
            $request->session()->put('verification_code', $code);
    
            // Send the verification code via email
            Mail::to($request->input('email'))->send(new AccountConfirmationMail);
    
            return $this->returnSuccessMessage('A verification code has been sent to your email.',200);
           
        } catch (ValidationException $e) {
            return $this->returnError($e->validator->errors()->first(),400);
          
        }
    }
    

    public function verifyCode(Request $request)
    {
        try {
            $this->validate($request, [
                'verification_code' => 'required|digits:6'
            ]);
            $email = $request->session()->get('email');
            $code = $request->session()->get('verification_code');
            if ($request->input('verification_code') == $code) {
                $request->session()->put('email', $email);
                return $this->returnSuccessMessage('Email address verified successfully',200);
            } else {
                throw ValidationException::withMessages([
                    'verification_code' => ['Invalid verification code.']
                ]);
            }
        } catch (ValidationException $e) {
            return $this->returnError($e->validator->errors()->first(),400);
        }
    }

    public function registerUser(Request $request) {
        // Validate the input data
    $validateUser = Validator::make($request->all(), [
        'first_name' => 'required',
        'last_name' => 'required',
        'phone' => 'required',
        'password' => 'required|string|min:8|confirmed',
        'address' => 'required',
        'personal_id' => 'required'
    ]);

    if ($validateUser->fails()) {
        // Throw a ValidationException with the appropriate error message
        throw ValidationException::withMessages($validateUser->errors()->toArray());
    }

    // Rest of the code for user registration...

    // Retrieve the email from the session
    $email = $request->session()->get('email');

    // Create a new user record in the database

    $user = new User();
    $user->first_name = $request->first_name;
    $user->last_name = $request->last_name;

    $user->address = $request->address;
    $user->photo = $request->photo;
    $user->email = $email;
    $user->password = bcrypt($request->password);
    $user->phone = $request->phone;
    $user->personal_id = $request->personal_id;

    // Rest of the code...

    // Save the user record
    $user->save();

    // Rest of the code...


    return $this->returnData('User registered successfully.',$token,)
    return response()->json([
        'message' => 'User registered successfully.',
        'access_token' => $token,
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
