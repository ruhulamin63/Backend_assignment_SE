<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\SignInUserRequest;
use App\Http\Requests\SignupUserRequest;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class AuthController extends Controller
{
    use HttpResponses;

    public function signin(SignInUserRequest $request){
        if (isset($request->email) && isset($request->password)) {
            $request->validated($request->all());
            if (!Auth::attempt($request->only('email', 'password'))) {
                return $this->error([
                    'message' => 'Invalid login credentials'
                ], 401);
            }
        }

        $user = Auth::user();
        $token_for = 'Web';

        $token = $user->createToken($token_for. ' - API Token of ' . $user->name)->plainTextToken;
        $user = UserResource::make($user);

        return $this->success([
            'message' => 'Authentication successful',
            'user' => $user,
            'token' => $token
        ],200);
    }

    public function logout(){
        $token = auth()->user()->currentAccessToken();
        $token->delete();

        return $this->success([
            'message' => 'Logged out successfully',
        ],200);
    }

    public function verifyEmail(Request $request){
        $request->validate([
            'id' => 'required|integer',
            'hash' => 'required|string'
        ]);

        $user = User::findOrFail($request->id);

        if ($user->hasVerifiedEmail()) {
            return $this->error([
                'status' => 'error',
                'message' => 'Email already verified.'
            ], 400);
        }

        if ($user->markEmailAsVerified()) {
            $message = 'Email verified successfully.';
            return redirect()->away(env('FRONTEND_URL') . '/auth')->with('success', $message);
        }

        return $this->error([
            'status' => 'error',
            'message' => 'Email verification failed.'
        ], 400);
    }

    public function resendVerificationEmail(Request $request){
        $request->validate([
            'email' => 'required|email'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->error([
                'status' => 'error',
                'message' => 'User not found.'
            ], 404);
        }

        if ($user->hasVerifiedEmail()) {
            return $this->error([
                'status' => 'error',
                'message' => 'Email already verified.'
            ], 400);
        }

        $user->sendEmailVerificationNotification();

        return $this->success([
            'status' => 'success',
            'message' => 'Email verification link sent successfully.'
        ], 200);
    }
}
