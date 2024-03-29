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
}
