<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\UserController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Products\ProductController;
use App\Http\Controllers\Products\CategoryController;

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

// make a sanctum/csrf-cookie available to the frontend
Route::get('/sanctum/csrf-cookie', function (Request $request) {
    return response()->json(['csrf' => csrf_token()]);
});

// Public routes
Route::group(['prefix' => 'auth'], function () {
    // Route::post('/signup', [AuthController::class, 'signup']);
    Route::post('/signin', [AuthController::class, 'signin']);
});

Route::post('forget-password', [UserController::class, 'submitForgetPassword']);
Route::post('get-password-reset-email', [UserController::class, 'getPasswordResetEmail']);
Route::post('reset-password', [UserController::class, 'submitResetPassword']);

// Protected routes
Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::apiResource('users', UserController::class);
    Route::post('profile', [UserController::class, 'profileUpdate']);

    Route::get('/me', [UserController::class, 'me']);
    //profiles update
    Route::post('/update-password', [UserController::class, 'updatePassword']);

    Route::get('/clicked', function () {
        echo '';
    });

     //notification
     Route::get('test-notification', [NotificationController::class, 'testNotification']);
     Route::get('test-firebase-notification', [NotificationController::class, 'testFirebaseNotification']);
     Route::get('get-notifications', [NotificationController::class, 'getNotifications']);
     Route::get('get-limit-notifications', [NotificationController::class, 'getLimitNotifications']);
     Route::post('mark-as-read', [NotificationController::class, 'markAsRead']);

    Route::apiResource('products', ProductController::class);
    Route::apiResource('categories', CategoryController::class);
    Route::get('categories-list', [CategoryController::class, 'allCategories']);
});
