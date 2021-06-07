<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use DB;

class UserModel extends Model
{
    public static function registerInsert($regData){
        $regResults = DB::table('registration')->insert($regData);
        return $regResults;
    }
    
    public static function medicationInsert($medicationData){
    	$medicationResults = DB::table('medication')->insert($medicationData);
    	return $medicationResults;
    }
    
    public static function dietInsert($dietData){
    	$dietResults = DB::table('diet')->insert($dietData);
    	return $dietResults;
    }
     
    public static function moodInsert($moodData){
    	$moodResults = DB::table('mood')->insert($moodData);
    	return $moodResults;
    }
    
    public static function symptomInsert($symptom, $insertData){
    	$insResults = DB::table($symptom)->insert($insertData);
    	return $insResults;
    }
    
    public static function activityInsert($activityData){
    	$activityResults = DB::table('activity')->insert($activityData);
    	return $activityResults;
    }
    
    public static function emailExists($email){
    	$emailExists = DB::table('users')->where('email', '=', $email)->exists();
    	return $emailExists;
    }

    public static function userDetails($email){
        $userDetails = DB::table('users')->
        select('id','email','remember_token','created_at','updated_at')->
        where('email', '=', $email)->first();
        return $userDetails;
    }
    
    public static function userDetailsById($userId){
    	$userDetails = DB::table('users')->where('id', '=', $userId)->first();
    	return $userDetails;
    }    
    
    public static function allUsers($limit = 0, $offset = 0){
    	if($limit || $offset) {
	    	$allUsers = DB::table('users')
	    	->select('users.id as id','users.email as email','first_name','last_name','date_of_birth','gender')
	    	->leftJoin('registration', 'users.id', '=', 'registration.user_id')
	    	->where([['users.email', '!=', 'superadmin@bladderunner.com'], ['registration.is_deleted', '=', '0']])
	    	->take($limit)
	    	->skip($offset)
	    	->get();
    	} else {
    		$allUsers = DB::table('users')
    		->leftJoin('registration', 'users.id', '=', 'registration.user_id')
    		->where([['registration.is_deleted', '=', '0']])
    		->count();
    	}
    	return $allUsers;
    }

    public static function registerUpdate($regUpdate, $userId){
        $updateResults = DB::table('registration')->where('user_id', '=', $userId)->update($regUpdate);
        return $updateResults;
    }
    
    public static function passwordUpdate($pwdUpdate, $userId){
    	$updateResults = DB::table('users')->where('id', '=', $userId)->update($pwdUpdate);
    	return $updateResults;
    }

    public static function profile($userId){
        $profileDetails = DB::table('registration')
        ->select('first_name','last_name', 'gender', 'age', 'date_of_birth', 'is_deleted', 'viewed_tnc')
        ->where('user_id', '=', $userId)->first();
        
        // Get an Encrypter implementation from the service container.
        $encrypter = app('Illuminate\Contracts\Encryption\Encrypter');
        
        $arrProfileDetails['first_name'] = $encrypter->decrypt($profileDetails->first_name);
        $arrProfileDetails['last_name'] = $encrypter->decrypt($profileDetails->last_name);
        $arrProfileDetails['gender'] = $encrypter->decrypt($profileDetails->gender);
        $arrProfileDetails['age'] = $encrypter->decrypt($profileDetails->age);
        $arrProfileDetails['date_of_birth'] = date('Y/m/d', strtotime($encrypter->decrypt($profileDetails->date_of_birth)));
        $arrProfileDetails['is_deleted'] = $profileDetails->is_deleted;
        $arrProfileDetails['viewed_tnc'] = $profileDetails->viewed_tnc;
        
        return $arrProfileDetails;
    }
    
    public static function medicationData($userId, $date){
    	$medicationData = DB::table('medication')
    	->select('drug_name', 'qty', 'dosage', 'time')
    	->where([['user_id', '=', $userId],[DB::raw("DATE_FORMAT(client_datetime, '%Y/%m/%d')"), '=', $date]])->get();
    	return $medicationData;
    }
    
    public static function dietData($userId, $date, $type){
    	$dietData = DB::table('diet')
    	->select('desc', 'qty', 'type')
    	->where([['user_id', '=', $userId],[DB::raw("DATE_FORMAT(client_datetime, '%Y/%m/%d')"), '=', $date], ['type', '=', $type]])->get();
    	return $dietData;
    }
    public static function aboutUsContent(){
        
    	$content = DB::table('about_us')
    	->select('about_us_content')
        ->where('id', '=', '2')->get();
    	 return $content;
    }
    
    public static function moodData($userId, $date){
    	$moodData = DB::table('mood')
    	->select('message', 'emoji_code')
    	->where([['user_id', '=', $userId],[DB::raw("DATE_FORMAT(client_datetime, '%Y/%m/%d')"), '=', $date]])->get();
    	return $moodData;
    }
    
    public static function activityData($userId, $date){
    	$activityData = DB::table('activity')
    	//->select('name', 'desc', 'start_time', 'end_time')
    	->select('name', 'desc', 'start_time')
    	->where([['user_id', '=', $userId],[DB::raw("DATE_FORMAT(client_datetime, '%Y/%m/%d')"), '=', $date]])->get();
    	return $activityData;
    }
    
    public static function symptomData($symptom, $userId, $date){
    	$symptomData = DB::table($symptom)
    	->select('time','severity','duration')
    	->where([['user_id', '=', $userId],[DB::raw("DATE_FORMAT(client_datetime, '%Y/%m/%d')"), '=', $date]])->get();
    	return $symptomData;
    }

    public static function symptomsDaily($symptom, $userId, $date){
    	$column = $symptom.'_no';
    	$symptomData= DB::table($symptom)->where([['user_id', '=', $userId],[DB::raw("DATE_FORMAT(client_datetime, '%Y/%m/%d')"), '=', $date]])->sum($column);
        $symptomData = ($symptomData == 0)?strval($symptomData):$symptomData;
    	return $symptomData;
    }
    
    public static function symptomsWeekly($symptom, $userId, $year, $month){
    	$column = $symptom.'_no';
    	$symptomData= DB::table($symptom)
    	->select(DB::raw("sum($column)/7 as avg"), DB::raw('WEEK(client_datetime) -
    WEEK(client_datetime - INTERVAL DAY(client_datetime)-1 DAY) + 1 as week_number'))
    	->groupBy('week_number')
    	->where([['user_id', '=', $userId],[DB::raw('year(client_datetime)'), '=', $year],
    			[DB::raw('month(client_datetime)'), '=', $month]
    	])
    	->orderBy('week_number','desc')
    	->get();
    	return $symptomData;
    }
    
    public static function symptomsMonthly($symptom, $userId, $year){
    	$column = $symptom.'_no';
    	$symptomData= DB::table($symptom)
    	->select(DB::raw("sum($column) as sum"),DB::raw('month(client_datetime) as month'))
    	->groupBy('month')
    	->where([['user_id', '=', $userId],[DB::raw('year(client_datetime)'), '=', $year]])
    	->orderBy('month','desc')
    	->get();
    	//->sum($column);
    	return $symptomData;
    }
    
    public static function symptomsMonthlyReport($symptom, $userId, $year, $month){
    	$column = $symptom.'_no';
    	$symptomData= DB::table($symptom)
    	->select(DB::raw("sum($column) as sum"),DB::raw('day(client_datetime) as day'))
    	->groupBy('day')
    	->where([['user_id', '=', $userId],[DB::raw('year(client_datetime)'), '=', $year], 
    			[DB::raw('month(client_datetime)'), '=', $month]
    	] )
    	->orderBy('day','desc')
    	->get();
    	//->sum($column);
    	return $symptomData;
    }
    
    public static function medicationMonthlyReport($userId, $year, $month){

        $medicationMonthlyData= DB::table('medication')
    	->select(DB::raw("GROUP_CONCAT(drug_name SEPARATOR ', ') as medication"),DB::raw('day(client_datetime) as day'))
    	->groupBy('day')
    	->where([['user_id', '=', $userId],[DB::raw('year(client_datetime)'), '=', $year],
    			[DB::raw('month(client_datetime)'), '=', $month]
    	] )
    	->orderBy('day','desc')
    	->get();
    	//->sum($column);
    	return $medicationMonthlyData;
    }
}
