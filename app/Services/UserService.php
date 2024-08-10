<?php

namespace App\Services;

use App\Http\Traits\GeneralTrait;
use App\Mail\AccountConfirmationMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Actions\FileUploadAction;
use Laravel\Passport\Bridge\UserRepository;
use Symfony\Component\Mailer\Exception\TransportException;



class UserService {

    public function sendVerificationCode(Request $request)
    {
        $this->validateEmail($request);

        $code = random_int(100000, 999999);

        // Store the email and verification code in the session
        $request->session()->put('email', $request->input('email'));
        $request->session()->put('verification_code', $code);

        // Retry sending email up to 3 times with a delay between retries
        retry(3, function () use ($request) {
            Mail::to($request->input('email'))->send(new AccountConfirmationMail);
        }, 1000); // 1000 milliseconds delay between retries
    }

    public function verifyCode(Request $request)
    {
        $this->validateVerificationCode($request);

        $email = $request->session()->get('email');
        $code = $request->session()->get('verification_code');

        if ($request->input('verification_code') == $code) {
            $request->session()->put('email', $email);
            return true;
        }

        throw ValidationException::withMessages([
            'verification_code' => ['Invalid verification code.']
        ]);
    }

    public function registerUser(array $userData, $isAdmin = false)
    {
        // Additional validation for admin creating users
        if ($isAdmin) {
            $validator = Validator::make($userData, [
                'first_name'  => 'required',
                'last_name'   => 'required',
                'phone'       => 'required',
                'password'    => 'required|string|min:8|confirmed',
                'address'     => 'required',
                'personal_id' => 'required',
                'email'       => 'required|email|unique:users,email',
                'permission_id' => 'required|in:1,2', // Validate permission_id to be 1 or 2
            ]);
        } else {
            $validator = Validator::make($userData, [
                'first_name'  => 'required',
                'last_name'   => 'required',
                'phone'       => 'required',
                'password'    => 'required|string|min:8|confirmed',
                'address'     => 'required',
                'personal_id' => 'required'
            ]);
        }

        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }

        $email = $isAdmin ? $userData['email'] : session()->get('email');

        $user = new User();
        $user->first_name = $userData['first_name'];
        $user->last_name = $userData['last_name'];
        $user->address = $userData['address'];
        $user->email = $email;
        $user->password = bcrypt($userData['password']);
        $user->phone = $userData['phone'];
        $user->personal_id = $userData['personal_id'];

        if (isset($userData['photo'])) {
            $photo = $userData['photo'];
            $photoExtension = time() . '.' . $photo->getClientOriginalExtension();
            $photo->move(public_path('uploads/users_photo'), $photoExtension);
            $user->photo = 'uploads/users_photo/' . $photoExtension;
        }

        $user->save();
        $token = $user->createToken("auth_token")->accessToken;

        // Clear the session data if not an admin
        if (!$isAdmin) {
            session()->forget('email');
        }

        return $token;
    }

    public function validateEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email'
        ]);
    }

    private function validateVerificationCode(Request $request)
    {
        $request->validate([
            'verification_code' => 'required|digits:6'
        ]);
    }

    public function login(array $userData)
    {

        $validator = Validator::make($userData, [

            "email" => "required",
            "password" => "required",
        ]);


        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }


            if (!auth()->attempt($userData)){
                throw ValidationException::withMessages([
                    'email' => ['Invalid credentials'],
                ]);
            }



            $token = auth()->user()->createToken("auth_token")->accessToken;

            return $token;

    }

    public function getProfile($userId)
    {
        $user = User::find($userId);
        if (is_null($user)) {
            throw new \Exception('User Not Found');
        }

        $user->makeHidden(['verification_code', 'verified', 'email']);
        return $user;
    }

    public function updateProfile(User $user, array $profileData)
    {
        $validator = Validator::make($profileData, [
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable',
            'photo' => 'nullable|image|max:2048',
            'current_password' => 'required_with:new_password',
            'new_password' => 'nullable|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $user->first_name = $profileData['first_name'] ?? $user->first_name;
        $user->last_name = $profileData['last_name'] ?? $user->last_name;
        $user->phone = $profileData['phone'] ?? $user->phone;
        $user->address = $profileData['address'] ?? $user->address;

        if (isset($profileData['photo'])) {
            $photo = $profileData['photo'];
            if ($user->photo && file_exists(public_path($user->photo))) {
                unlink(public_path($user->photo));
            }

            $photoExtension = time() . '.' . $photo->getClientOriginalExtension();
            $photo->move(public_path('uploads/users_photo'), $photoExtension);
            $user->photo = 'uploads/users_photo/' . $photoExtension;
        } elseif (isset($profileData['delete_photo'])) {
            if ($user->photo && file_exists(public_path($user->photo))) {
                unlink(public_path($user->photo));
            }

            $user->photo = null;
        }

        if (isset($profileData['new_password'])) {
            if (!Hash::check($profileData['current_password'], $user->password)) {
                throw new \Exception('The current password is incorrect.');
            }

            $user->password = Hash::make($profileData['new_password']);
        }

        $user->save();
        return $user;
    }
}

