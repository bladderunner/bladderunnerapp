<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use Edujugon\PushNotification\PushNotification;
use App\Services\PushNotificationsModel;
use Illuminate\Support\Facades\Log;

class SendPNForActivities extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'SendPNForActivities:sendpush';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Push Notification for activities';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
	   
        $activityData = DB::table('activity')
                ->leftJoin('registration', 'activity.user_id', '=', 'registration.user_id')
                ->select('activity.id', 'activity.name', 'registration.first_name', 'registration.user_id', 'registration.device_token', 'activity.start_time', 'activity.client_timezone_offset', 'registration.device_type', 'activity.is_triggered')
                ->where([
                    [DB::raw("DATE_FORMAT(DATE_ADD(now() , INTERVAL activity.client_timezone_offset HOUR_MINUTE),'%Y/%m/%d')"), '=',
                        DB::raw("DATE_FORMAT(activity.client_datetime, '%Y/%m/%d')")]
                ])
		->get();
	   // Log::info(" push notification activity started ");

        $decrypter = app('Illuminate\Contracts\Encryption\Encrypter');
        foreach ($activityData as $activity) {

            $activityId = $activity->id;
            $activityName = $activity->name;
            $firstName = $decrypter->decrypt($activity->first_name);
            $deviceToken = $activity->device_token;
            $deviceType = $activity->device_type;
            $timezoneOffset = $activity->client_timezone_offset;
            $startTime = $activity->start_time;
            $isTriggered = $activity->is_triggered;

            $trgUpdate = array(
                'is_triggered' => 1,
            );
            if ($isTriggered == 0) {

                $nowTs = strtotime("now");
                $timezoneInSeconds = explode(':', $timezoneOffset);
                $hoursToSeconds = $timezoneInSeconds[0] * 60 * 60;
                $minToSeconds = $timezoneInSeconds[1] * 60;
                $tzTs = $hoursToSeconds + $minToSeconds;
                $nowTsUXT = $nowTs + $tzTs;  //current server time in UNIXTIMESTAMP

                $serverTriggerTime = $nowTsUXT + 900; // added 15 min to current server time in timestamp;
                $userTriggerTime = strtotime($startTime); //actual user given time in timestamp

                if ($userTriggerTime <= $serverTriggerTime) {
                    $updateTrigger = DB::table('activity')->where('id', '=', $activityId)->update($trgUpdate);
                    $body["title"] = $activityName;
                    $body['message'] = "Dear " . $firstName . " , Your scheduled activity is with in 15 min.";
                    if ($deviceType == 'Android') {
                        PushNotificationsModel::sendNotificationsInFCM($deviceToken, $body);
		    } else {
			    Log::info("ios device token" . $deviceToken);
                        PushNotificationsModel::sendNotificationsInFCM($deviceToken, $body);
                    }
                }
            }
        }
    }

}
