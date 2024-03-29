<?php

namespace App\Http\Controllers;

use App\Models\DetailsUser;
use App\Models\EnrollUser;
use App\Models\RtoUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Http\Resources\UserResource;
use App\Http\Resources\CommonResource;
use App\Traits\HttpResponses;
use Illuminate\Support\Facades\File;
use App\Rules\BangladeshiPhoneNumber;
use Illuminate\Validation\Rule;

class UserController extends Controller
{

    use HttpResponses;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request){
        $users = User::latest();

        if ($request->with)
            $users->with(json_decode($request->with));

        if ($request->search)
            $users->where(function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->search . '%')->orWhere('email', 'like', '%' . $request->search . '%');
            });

        if ($request->type)
            $users->where('type', $request->type);

        if ($request->id)
            $users->where('id', $request->id);

        if ($request->rows) {
            $users = $users->paginate($request->rows);
        } else {
            $users = $users->get();
        }

        return CommonResource::collection($users);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request){
        $request->validate([
            'name'           => 'required|string|min:3',
            'email'          => 'required|email|unique:users',
            'password'       => 'required|string|min:6|confirmed',
            'type'           => 'required|in:admin,mentor,assessor,rto,student',
            'roles'          => 'required|array|min:1',
        ]);

        DB::beginTransaction();
        try {
            $user = User::create([
                'name'           => $request->name,
                'email'          => $request->email,
                'type'           => $request->type,
                'password'       => Hash::make($request->password)
            ]);

            DB::commit();
            return message("User Created Successfully", 200);
        } catch (\Throwable $th) {
            DB::rollback();
            return message($th->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(User $user){
        $user->load('roles.permissions');

        return UserResource::make($user);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user){
        $request->validate([
            'name'           => 'required|string|min:3',
            'email'          => 'required|email|unique:users,email,' . $user->id,
            'designation_id' => 'required|integer',
            'password'       => 'nullable|string|min:6|confirmed',
            'type'           => 'required|in:official,exen,ae,dlc',
            'roles'          => 'required|array|min:1',
        ]);

        DB::beginTransaction();
        try {
            $update_data = array(
                'name'           => $request->name,
                'email'          => $request->email,
                'designation_id' => $request->designation_id,
                'type'           => $request->type,
            );

            if ($request->password) {
                $update_data['password'] = Hash::make($request->password);
            }

            $user->update($update_data);

            $user->roles()->sync($request->roles);


            DB::commit();
            return message("User Updated Successfully", 200);
        } catch (\Throwable $th) {
            DB::rollback();
            return message($th->getMessage(), 400);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id){
        try {
            $user = User::findOrFail($id);
            if ($user)
                $user->delete();

            return message("User Deleted Successfully", 200);
        } catch (\Throwable $th) {
            return message($th->getMessage(), 400);
        }
    }

    public function submitForgetPassword(Request $request){
        $request->validate([
            'email' => 'required|email|exists:users',
        ]);

        try {

            $token = str()->random(64);

            DB::table('password_reset_tokens')->insert([
                'email' => $request->email,
                'token' => $token,
                'created_at' => Carbon::now()
            ]);

            Mail::send('email.forgetPassword', ['token' => $token], function ($message) use ($request) {
                $message->to($request->email);
                $message->subject('Reset Password');
            });

            return message('We have e-mailed your password reset link!', 200);
        } catch (\Throwable $th) {
            return message($th->getMessage(), 400);
        }
    }

    public function getPasswordResetEmail(Request $request){
        $res = '';
        if (isset($request->token)) {
            $data = DB::table('password_reset_tokens')->where(['token' => $request->token])->first();

            if ($data) {
                $res = $data->email;
            }
        }
        echo $res;
    }

    public function submitResetPassword(Request $request){

        $request->validate([
            'email' => 'required|email|exists:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $updatePassword = DB::table('password_reset_tokens')
            ->where([
                'email' => $request->email,
                'token' => $request->token
            ])
            ->first();

        if (!$updatePassword) {
            return $this->error([
                'status' => 'error',
                'message' => 'Invalid token!'
            ], 400);
        }

        User::where('email', $request->email)
            ->update(['password' => Hash::make($request->password)]);

        DB::table('password_reset_tokens')->where(['email' => $request->email])->delete();

        return message('Your password has been changed!', 200);
    }

    public function me(){
        return UserResource::make(auth()->user());
    }

    /**
     * Profile Update
     */
    public function profileUpdate(Request $request){
        DB::beginTransaction();
        try {
            $validation = [
                'name'           => 'required|string|min:3',
                'email'          => 'required|email|unique:users,email,' . auth()->user()->id,
                'phone' => [
                    'required',
                    new BangladeshiPhoneNumber,
                    Rule::unique('users')->where(function ($query) {
                        return $query->where('type', 'le');
                    }),
                ],
            ];
            $request->validate($validation);

            if(!auth()->user()->phone){
                foreach (User::pluck('phone')->toArray() as $key => $value) {
                    if($value == $request->phone){
                        return response()->json([
                            'status' => 409,
                            'message' => 'The phone has already been taken.',
                        ]);
                    }
                }
            }

            if(!auth()->user()->email){
                foreach (User::pluck('email')->toArray() as $key => $value) {
                    if($value == $request->email){
                        return response()->json([
                            'status' => 409,
                            'message' => 'The email has already been taken.',
                        ]);
                    }
                }
            }

            $update_data = array(
                'name'           => $request->name,
                'email'          => $request->email,
                'phone'            => $request->phone,
            );

            if ($request->hasFile('image')) {

                $auth_user_photo = auth()->user()->photo;
                $image_path = public_path($auth_user_photo);
                if (File::exists($image_path)) {
                    File::delete($image_path);
                }

                $image = $request->file('image');
                $randomNumber = str_pad(mt_rand(1, 999999999999), 12, '0', STR_PAD_LEFT);
                $avatar_image_new_name = $randomNumber . '_'  . $image->getClientOriginalName();
                $image->move('uploads/profiles', $avatar_image_new_name);
                $img = 'uploads/profiles/' . $avatar_image_new_name;


                $update_data['photo'] = $img;
            }
            auth()->user()->update($update_data);

            DB::commit();
            return response()->json([
                'status' => 200,
                'message' => 'Profile Updated Successfully',
            ], 200);
        } catch (\Throwable $th) {
            DB::rollback();
            return response()->json([
                'message' => $th->getMessage(),
            ]);
        }
    }

    public function updatePassword(Request $request){
        if(!$request->user_id){
            $validation['current_password'] = 'required';
        }
        $validation['password'] = 'required|min:6|confirmed';

        $request->validate($validation);

        DB::beginTransaction();
        try {
            if($request->user_id){
                $user = User::find($request->user_id);
            }else{
                $user = auth()->user();

                // Check if the provided current password matches the user's actual password
                if (!Hash::check($request->current_password, $user->password)) {
                    return response()->json([
                        'status' => 409,
                        'message' => "Current Password doesn't match",
                    ]);
                }

                // Current password and new password same
                if (strcmp($request->current_password, $request->password) == 0) {
                    return response()->json([
                        'status' => 409,
                        'message' => "New Password cannot be same as your current password.",
                    ]);
                }
            }

            // Update the user's password
            $user->update(['password' => Hash::make($request->password)]);

            DB::commit();
            return response()->json([
                'status' => 200,
                'message' => 'Password Updated Successfully',
            ], 200);
        } catch (\Throwable $th) {
            DB::rollback();
            return response()->json([
                'message' => $th->getMessage(),
            ]);
        }
    }
}
