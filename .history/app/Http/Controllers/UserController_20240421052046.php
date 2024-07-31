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
use Illuminate\Database\QueryException;
class UserController extends Controller
{
    use GeneralTrait;
    public function sendVerificationCode(Request $request)
    {
        try {
            $this->validate($request, [
                'email' => 'required|email|unique:users,email'
            ]);
            $code = random_int(100000, 999999);
            $request->session()->put('email', $request->input('email'));
            $request->session()->put('verification_code', $code);
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

    public function registerUser(Request $request)
     {
        try{
        $validateUser = Validator::make($request->all(), [
            'first_name'  => 'required',
            'last_name'   => 'required',
            'phone'       => 'required',
            'password'    => 'required|string|min:8|confirmed',
            'address'     => 'required',
            'personal_id' => 'required'
        ]);
    
        if ($validateUser->fails()) {
            return $this->returnError($validateUser->errors(), 'Validation Error');
        }
    
        // Retrieve the email from the session
        $email = $request->session()->get('email');
    
        // Create a new user record in the database
        $user = new User();
        $user->first_name = $request->input('first_name');
        $user->last_name = $request->input('last_name');
        $user->address = $request->input('address');
        $user->email = $email;
        $user->password = bcrypt($request->input('password'));
        $user->phone = $request->input('phone');
        $user->personal_id = $request->input('personal_id');
    
        // Upload user photo if provided
        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            $photoExtension = time() . '.' . $photo->getClientOriginalExtension();
            $photo->move(public_path('uploads/users_photo'), $photoExtension);
            $photoPath = 'uploads/users_photo/' . $photoExtension;
            $user->photo = $photoPath;
        }
    
        $user->save();
        $token = $user->createToken("auth_token")->accessToken;
    
        // Clear the session data
        $request->session()->forget('email');
    
        return $this->returnData('User Registered Successfully.', $token, 200);
    } catch (QueryException $e) {
        return $this->returnError($e->getMessage(), 'Database Error');
    }
}
    public function login(Request $request)
    {
        try {
            $login_data = $request->validate([
                "email" => "required",
                "password" => "required",
            ]);

            if (!auth()->attempt($login_data)){
                throw ValidationException::withMessages([
                    'email' => ['Invalid credentials'],
                ]);
            }
            $token = auth()->user()->createToken("auth_token")->accessToken;
            return $this->returnData('User logged in successfully.', $token, 200);
        } catch (ValidationException $e) {
            return $this->returnError($e->validator->errors()->first(),400);

        }
    }

    public function myProfile()
    {
        $user_data = auth()->user()->makeHidden(['verification_code', 'verified']);
        return $this->returnData('user data.', $user_data, 200);
    }

    public function getProfile($user_id)
    {
        $user = User::find($user_id);

        if (!$user) {
            return $this->returnError($user->errors(),'User Not Found',404);
        }
        else {
            $user->makeHidden(['verification_code', 'verified','email']);
        }
        return $this->returnData('User profile data', $user, 200);

    }

    //logout-get
    public function logout()
    {

        $LogOut=auth()->user()->token()->delete();
if(!$LogOut){
    return $this->returnError($user->errors(),'User Not Found',404);

}
        return $this->returnSuccessMessage('user log out successfully',200);


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
        $inputPassword = $request->input('password');
        $hashedPassword = auth()->user()->password;
    
        $validator = Validator::make(
            ['password' => $inputPassword],
            ['password' => 'required']
        );
    
    
    
        if (Hash::check($inputPassword, $hashedPassword)) {
            return $this->returnSuccessMessage('Valid password', 200);
        } else {
            return $this->returnErrorMessage('Invalid password',400);
        }
    }

}



