<?php

namespace App\Services;

use Edujugon\PushNotification\PushNotification;
use Response;
use Illuminate\Support\Facades\Log;

class PushNotificationsModel {

    public static function sendNotificationsInFCM($deviceToken, $message) {
        $push = new PushNotification('fcm');
        $push->setMessage([
                    'notification' => [
                        'title' => $message['title'],
                        'body' => $message['message'],
                        'sound' => 'default'
                    ],
                    'data' => [
                        'extraPayLoad1' => 'value1',
                        'extraPayLoad2' => 'value2'
                    ]
                ])
                ->setApiKey('AAAAs957jPY:APA91bFUT_fueRnF-L6zuYi_-eNN4Jel2lRWlOXw9sSf8t1TNIEfm-_0CzBHQfslnOQjbF51EZARnYMz1TI-MZ4Y8XfHABHbFK52djQPe2ObhWGv52h-AEKGbWbZckAL61tVuvn7ir6g')
                ->setDevicesToken([$deviceToken])
                ->send();

        $pushReturn = $push->getFeedback();
//        var_dump($pushReturn);
    }

    public static function sendNotificationsInAPN($deviceToken, $message) {
       
        $push = new PushNotification('apn');
        $message = [
            'aps' => [
                'alert' => [
                    'title' => $message['title'],
                    'body' => $message['message']
                ],
                'sound' => 'default'
            ]
        ];
        $push->setMessage($message)
                ->setDevicesToken(
                    $deviceToken
                  );
         $push = $push->send();
	$feedback = $push->getFeedback();
//	var_dump($feedback);
        Log::info(var_dump($feedback));
    }
    

}

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

