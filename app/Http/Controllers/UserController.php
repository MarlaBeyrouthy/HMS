<?php

namespace App\Http\Controllers;

use App\Mail\AccountConfirmationMail;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Http\Traits\GeneralTrait;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;
use Symfony\Component\Mailer\Exception\TransportException;

class UserController extends Controller
{


    use GeneralTrait;



    /*
    public function sendVerificationCode(Request $request)
    {
        try {
            $this->validate($request, [
                'email' => 'required|email|unique:users,email'
            ]);

            $code = random_int(100000, 999999);

            // Store the email and verification code in the session
            $request->session()->put('email', $request->input('email'));
            $request->session()->put('verification_code', $code);

            Mail::to($request->input('email'))->send(new AccountConfirmationMail);
            return $this->returnSuccessMessage('A verification code has been sent to your email.',200);
        } catch (ValidationException $e) {
            return $this->returnError($e->validator->errors()->first(),400);
        }
    }

    */
    /*
    public function sendVerificationCode(Request $request)
    {
        try {
            $this->validate($request, [
                'email' => 'required|email|unique:users,email'
            ]);

            $code = random_int(100000, 999999);

            // Store the email and verification code in the session
            $request->session()->put('email', $request->input('email'));
            $request->session()->put('verification_code', $code);

            // Retry sending email up to 3 times with a delay between retries
            retry(3, function () use ($request) {
                Mail::to($request->input('email'))->send(new AccountConfirmationMail);
            }, 1000); // 1000 milliseconds delay between retries

            return $this->returnSuccessMessage('A verification code has been sent to your email.', 200);
        } catch (ValidationException $e) {
            return $this->returnError($e->validator->errors()->first(), 400);
        } catch (TransportException $exception) {
            // If unable to establish connection after retries, return an error
            return $this->returnError('Failed to send verification code. Please try again later.', 500);
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
*/
    /*
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
    */


    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function sendVerificationCode(Request $request)
    {
        try {
            $this->userService->sendVerificationCode($request);
            return $this->returnSuccessMessage('A verification code has been sent to your email.', 200);
        } catch (ValidationException $e) {
            return $this->returnError($e->validator->errors()->first(), 400);
        } catch (\Exception $exception) {
            return $this->returnError('Failed to send verification code. Please try again later.', 500);
        }
    }

    public function verifyCode(Request $request)
    {
        try {
            $this->userService->verifyCode($request);
            return $this->returnSuccessMessage('Email address verified successfully', 200);
        } catch (ValidationException $e) {
            return $this->returnError($e->validator->errors()->first(), 400);
        } catch (\Exception $e) {
            return $this->returnError($e->getMessage(), 400);
        }
    }

    public function registerUser(Request $request)
    {
        try {
            $userData = $request->only([
                'first_name', 'last_name', 'phone', 'password', 'address', 'personal_id', 'photo','password_confirmation'
            ]);
            $token = $this->userService->registerUser($userData);
            return $this->returnData('User Registered Successfully.', $token, 200);
        } catch (\Exception $e) {
            return $this->returnError($e->getMessage(), 'Registration Error');
        }
    }
/*
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
*/
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
        return $this->returnData('user data.', $user_data, 200);
    }

    public function getProfile($user_id)
    {
        try {
            $user = $this->userService->getProfile($user_id);
            return $this->returnData('User profile data', $user, 200);
        } catch (\Exception $e) {
            return $this->returnError('User Not Found', 'User Not Found', 404);
        }
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
    //logout-get
    public function logout()
    {

        auth()->user()->token()->delete();

        return $this->returnSuccessMessage('user log out successfully',200);


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



