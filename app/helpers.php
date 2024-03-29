<?php

use App\Models\Leave;
use App\Models\Holiday;
use App\Models\Setting;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Notification;
use App\Events\NotificationEvent;

    // file upload common function
    function fileUpload($image, $path = null, $name = null){
        $image_name = $image->getClientOriginalName();

        if($name){
            $image_name = $name.'.'.$image->getClientOriginalExtension();
        }
        if($path){
            $image->move(public_path($path), $image_name);
        }else{
            $image->move(public_path('uploads'), $image_name);
        }

        return $path.$image_name;
    }

    // unlink file common function
    function unlinkFile($filePath){
        if (file_exists($filePath)) {
            unlink($filePath);
            return true;
        }
        return false;
    }

    if (!function_exists('message')) {
        /**
         * Message response for the API
         *
         * @param string $message Message to return
         * @param int $statusCode Response code
         * @return \Illuminate\Http\JsonResponse
         */
        function message($message = "Operation successful", $statusCode = 200, $data = [])
        {
            return response()->json(['message' => $message, 'data' => $data, 'status' => $statusCode], $statusCode);
        }
    }


    if (!function_exists('image')) {
        /**
         * Image URL generating
         *
         * @param mixed $file File including path
         * @param string $name Default name to create placeholder image
         * @return string URL of the file
         */
        function image($file, $name = 'Avatar')
        {
            if (Storage::exists($file))
                // $url = asset('uploads/' . $file);
                $url = asset($file);
            else
                $url = 'https://i2.wp.com/ui-avatars.com/api/' . Str::slug($name) . '/400';

            return $url;
        }
    }

    if (!function_exists('user')) {

        /**
         * Get the authenticated user instance
         *
         * @return \Illuminate\Contracts\Auth\Authenticatable|null
         */
        function user()
        {
            return auth()->user();
        }
    }


    if (!function_exists('sendNotification')) {

        /**
         * Send a notification to multiple users.
         *
         * @param array  $users     An array of user instances or user IDs.
         * @param string $title     The title of the notification.
         * @param string $message   The message body of the notification (optional).
         * @param string $web_url   A web URL associated with the notification (optional).
         * @param string $app_url   An app URL associated with the notification (optional).
         * @param string $platform  The platform on which to send the notification ('all' by default).
         */
        function sendNotification(array $users, $title = null, $notify_app_title = null,  $message = null, $web_url = null, $app_url = null, $platform = 'all')
        {
            foreach ($users as $user) {
                // store to database
                $notification = Notification::create([
                    'user_id' => $user,
                    'title' => $title,
                    'app_title' => $notify_app_title,
                    'message' => $message,
                    'web_url' => $web_url,
                    'app_url' => $app_url,
                    'platform' => $platform,
                    'created_by' => auth()->id(),
                ]);

                // Send the notification
                event(new NotificationEvent($notification));
            }
        }
    }
