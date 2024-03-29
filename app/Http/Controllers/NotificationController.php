<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use App\Events\NotificationEvent;
use App\Http\Resources\CommonResource;

class NotificationController extends Controller
{
    public function getNotifications(Request $request){
        $notifications = Notification::with(['user', 'creator'])->latest();

        if(isset($request->user_id)){
            $notifications->where('user_id', $request->user_id);
        }

        if($request->search)
            $notifications->where('title', 'like', '%' . $request->search . '%');

        if($request->rows){
            $notifications = $notifications->paginate($request->rows);
        }else{
            $notifications = $notifications->get();
        }

        return CommonResource::collection($notifications);
    }

    public function getLimitNotifications(Request $request){
        $res = [
            'notifications' => [],
            'unread_count' => 0,
        ];
        
        if(isset($request->limit) && isset($request->user_id)){
            
            $res['notifications'] = Notification::where('user_id', $request->user_id)->latest()->limit($request->limit)->get();
            $res['unread_count'] = Notification::where('user_id', $request->user_id)->whereNull('read_at')->count();
        }

        return response()->json($res);
    }

    public function testNotification(){
        $test_notification = Notification::create([
            'user_id' => 5,
            'title' => '<strong>Hafizur rahman </strong> Complete a Work please check',
            'web_url' => '/twin_pit_latrine',
        ]);
        event(new NotificationEvent($test_notification));
    }

    public function markAsRead(Request $request) {
        if($request->user_id){
            if(isset($request->mark_all)){
                Notification::where('user_id', $request->user_id)->whereNull('read_at')->update(['read_at' => now()]);
            }elseif(isset($request->id)){
                Notification::where('user_id', $request->user_id)->whereNull('read_at')->whereId($request->id)->update(['read_at' => now()]);
            }

            return message("Mark as Read Successfully", 200);
        }

        return message('Something went wrong!', 400);
    }

    public function testFirebaseNotification(Request $request){
        $firebaseToken = ['fdCkhFWNQGm_1dL_NIaIGF:APA91bFctDERMu9I4_7TqI94inkLbvo27ZfcdEp1lBGbMxJdfcmhY81hxPTq65nabCEG-jFznbBlhwSEEchsa233tnNBdqp1LQr8zRQ975p8vAtRkS52T6vSkhcvuH5VBSwC5f4n_W5y'];
            
        $SERVER_API_KEY = env('FCM_API_SERVER_KEY');
    
        $data = [
            "registration_ids" => $firebaseToken,
            "notification" => [
                "title" => 'test firebase',
                "body" => 'test firebase for dphe',  
            ]
        ];
        $dataString = json_encode($data);
      
        $headers = [
            'Authorization: key=' . $SERVER_API_KEY,
            'Content-Type: application/json',
        ];
      
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
                 
        $response = curl_exec($ch);

        return response()->json($response);
    }
}
