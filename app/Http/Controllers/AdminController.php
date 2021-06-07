<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Auth;
use \Illuminate\Http\Response as Res;

use App\Services\UserModel;
use App\User;
use JWTFactory;
use JWTAuth;
use Validator;
use Response;

class AdminController extends Controller
{
	protected $successCode = Res::HTTP_OK;
	protected $badRequestCode = Res::HTTP_BAD_REQUEST;
	protected $serverError = Res::HTTP_INTERNAL_SERVER_ERROR;
	protected $unAuthorized = Res::HTTP_UNAUTHORIZED;
	protected $pageLimit = 12;
	
	public function users(Request $request)
	{
		$response['status'] = 400;
		$response['message'] = 'Invalid Attributes.';
		
		$userId = $request->get('user_id');
		if(!$userId) {
			$response['error_message'] = 'User Id is required.';
			return Response::json($response);
		}
		
		$pageNo = $request->get('page_no');
		if(!$pageNo) {
			$response['error_message'] = 'Page number is required.';
			return Response::json($response);
		}
		
		$userDetails = UserModel::userDetailsById($userId);
		
		if($userDetails->email != 'superadmin@bladderunner.com') {
			$response['error_message'] = 'You are not an authorized user.';
			return Response::json($response);
		}
		
		$allUsersCnt = UserModel::allUsers();
		$usersCount = $allUsersCnt-1;
		
		$pagesCount = 0;
		$userLimitedDetails = $arrData = $arrPageInfo = array();
		//If users are there
		if ($usersCount) {
			
			// Calculate offset for pagination
			$offset = (int) $pageNo * $this->pageLimit;
			$offset = $offset ? $offset - $this->pageLimit : 0;
			
			$pagesCount = ceil( $usersCount / $this->pageLimit);
			
			$userLimitedDetails = UserModel::allUsers($this->pageLimit, $offset);
		}
		
		if($pageNo == $pagesCount) {
			$currentPageSize = $usersCount % $this->pageLimit;
		} else {
			$currentPageSize = $this->pageLimit;
		}
		
		if(!count($userLimitedDetails)) { 
			$this->successCode = 400;
		} else {
			
			$arrUsers = array_values((array)$userLimitedDetails);
			
			// Get an Encrypter implementation from the service container.
			/*$encrypter = app('Illuminate\Contracts\Encryption\Encrypter');
			
			foreach ($arrUsers[0] as $singleUser) {
			$singleUser->first_name = $encrypter->decrypt($singleUser->first_name);
			$singleUser->last_name = $encrypter->decrypt($singleUser->last_name);
			$singleUser->date_of_birth = date('Y/m/d', strtotime($encrypter->decrypt($singleUser->date_of_birth)));
			$singleUser->gender = $encrypter->decrypt($singleUser->gender);
			} */
			
			$arrData['listOfUsers'] = $arrUsers[0];
			
			$arrPageInfo = [
				'totalUsers' => $usersCount,
				'maxPageSize' => $this->pageLimit,
				'currentPageSize' => $currentPageSize,
				'currentPageNumber' => (int)$pageNo
			];
			
			$arrData['pageInfo'] = $arrPageInfo;
		}
		
		return Response::json([
			'status' => $this->successCode,
			'message' => 'All users.',
			'data' => $arrData,
			//'pageInfo' => $arrPageInfo
		]);
	}
	
	public function monthlyReport(Request $request){
		$userId = $request->get('user_id');
		$selectedUserId = $request->get('selected_user_id');
		$year = $request->get('year');
		$month = $request->get('month');
		
		$response['status'] = 400;
		$response['message'] = 'Invalid Attributes';
		
		if(!$userId) {
			$response['error_message'] = 'User Id is required.';
			return Response::json($response);
		}
		
		if(!$year) {
			$response['error_message'] = 'Year is required.';
			return Response::json($response);
		}
		
		if(!$month) {
			$response['error_message'] = 'Month is required.';
			return Response::json($response);
		}
		
		if(!$selectedUserId) {
			$response['error_message'] = 'No User is selected.';
			return Response::json($response);
		}
		
		$userDetails = UserModel::userDetailsById($userId);
		
		if($userDetails->email != 'superadmin@bladderunner.com') {
			$response['error_message'] = 'You are not an authorized user.';
			return Response::json($response);
		}
		
		$noOfDays = cal_days_in_month(CAL_GREGORIAN,$month,$year);
		
		$arrDays = range(1,$noOfDays);
		
		$arrDailyData = array();
		foreach($arrDays as $day){
			$arrDailyData[$day]['day'] = $day;
			$arrDailyData[$day]['urinal_visits'] = '0.0000';
			$arrDailyData[$day]['loss_bladder_control'] = '0.0000';
			$arrDailyData[$day]['nocturia'] = '0.0000';
			$arrDailyData[$day]['urinary_hesitancy'] = '0.0000';
			$arrDailyData[$day]['urinary_urgency'] = '0.0000';
			$arrDailyData[$day]['medication'] = 'Nil';
		}
		
		$urinalVisits = UserModel::symptomsMonthlyReport('urinal_visits', $selectedUserId, $year, $month);
		$lossBladderControl = UserModel::symptomsMonthlyReport('loss_bladder_control', $selectedUserId, $year, $month);
		$nocturia = UserModel::symptomsMonthlyReport('nocturia', $selectedUserId, $year, $month);
		$urinaryHesitancy = UserModel::symptomsMonthlyReport('urinary_hesitancy', $selectedUserId, $year, $month);
		$urinaryUrgency = UserModel::symptomsMonthlyReport('urinary_urgency', $selectedUserId, $year, $month);
		$medicationDetails = UserModel::medicationMonthlyReport($selectedUserId, $year, $month);
		
		foreach($urinalVisits as $urinalVisitsOne) {
			$arrDailyData[$urinalVisitsOne->day]['urinal_visits'] = (string) round($urinalVisitsOne->sum, 2);
		}
		
		foreach($lossBladderControl as $lossBladderControlOne) {
			$arrDailyData[$lossBladderControlOne->day]['loss_bladder_control'] = (string) round($lossBladderControlOne->sum, 2);
		}
		
		foreach($nocturia as $nocturiaOne) {
			$arrDailyData[$nocturiaOne->day]['nocturia'] = (string) round($nocturiaOne->sum , 2);
		}
		
		foreach($urinaryHesitancy as $urinaryHesitancyOne) {
			$arrDailyData[$urinaryHesitancyOne->day]['urinary_hesitancy'] = (string) round($urinaryHesitancyOne->sum , 2);
		}
		
		foreach($urinaryUrgency as $urinaryUrgencyOne) {
			$arrDailyData[$urinaryUrgencyOne->day]['urinary_urgency'] = (string) round($urinaryUrgencyOne->sum , 2);
		}
		
		foreach($medicationDetails as $medicationDetail) {
			$arrDailyData[$medicationDetail->day]['medication'] = (string) $medicationDetail->medication;
		}
		
		return Response::json([
			'status' => $this->successCode,
			'message' => 'Leader Board Monthly Report Data.',
			'data' => array_values($arrDailyData)]);
	}
	
	public function profileDelete(Request $request){
		
		$response['status'] = 400;
		$response['message'] = 'Invalid Attributes';
		
		$userId = $request->get('user_id');
		if(!$userId) {
			$response['error_message'] = 'User Id is required.';
			return Response::json($response);
		}
		
		$deletingUserId = $request->get('deleting_user_id');
		if(!$deletingUserId) {
			$response['error_message'] = 'Deleting User Id is required.';
			return Response::json($response);
		}
		
		$userDetails = UserModel::userDetailsById($userId);
		
		if($userDetails->email != 'superadmin@bladderunner.com') {
			$response['error_message'] = 'You are not an authorized user.';
			return Response::json($response);
		}
		
		$profDelete = array(
			'is_deleted' => 1,
			'updated_at' => date('Y-m-d H:i:s')
		);
		
		$resDelete = UserModel::registerUpdate($profDelete, $deletingUserId);
		
		if(!empty($resDelete)){
			return Response::json(['status' => $this->successCode,
				'message' => 'Profile successfully deleted.',
				'user_id' => $userId,
				'deleting_user_id' => $deletingUserId
			]);
		}else{
			return Response::json([
				'status' => $this->serverError,
				'message' => 'There is some technical error. Please re-try after some time.'
			]);
		}
	}
	
	public function profileUpdate(Request $request){
		
		$response['status'] = 400;
		$response['message'] = 'Invalid Attributes';
		
		$userId = $request->get('user_id');
		if(!$userId) {
			$response['error_message'] = 'User Id is required.';
			return Response::json($response);
		}
		
		$fname = $request->get('first_name');
		if(!$fname) {
			$response['error_message'] = 'First Name is required.';
			return Response::json($response);
		}
		
		$lname = $request->get('last_name');
		if(!$lname) {
			$response['error_message'] = 'Last Name is required.';
			return Response::json($response);
		}
		
		$dob = $request->get('date_of_birth');
		if(!$dob) {
			$response['error_message'] = 'Date Of Birth is required.';
			return Response::json($response);
		}
		
		$age = $request->get('age');
		if(!$age) {
			$response['error_message'] = 'Age is required.';
			return Response::json($response);
		}
		
		$gender = $request->get('gender');
		if(!$gender) {
			$response['error_message'] = 'Gender is required.';
			return Response::json($response);
		}
		
		$selectedUserId = $request->get('selected_user_id');
		
		if(!$selectedUserId) {
			$response['error_message'] = 'No User is selected to update profile.';
			return Response::json($response);
		}
		
		$userDetails = UserModel::userDetailsById($userId);
		
		if($userDetails->email != 'superadmin@bladderunner.com') {
			$response['error_message'] = 'You are not an authorized user.';
			return Response::json($response);
		}
		
		$profUpdate = array(
			'first_name' => $request->get('first_name'),
			'last_name' => $request->get('last_name'),
			'date_of_birth' => $request->get('date_of_birth'),
			'age' => $request->get('age'),
			'gender' => $request->get('gender'),
			'updated_at' => date('Y-m-d H:i:s')
		);
		
		$regUpdate = UserModel::registerUpdate($profUpdate, $userId);
		
		if(!empty($regUpdate)){
			$userDetails = UserModel::profile($userId);
			return Response::json(['status' => $this->successCode,
				'message' => 'Profile successfully updated.',
				'data' => $profUpdate]);
		}else{
			return Response::json([
				'status' => $this->serverError,
				'message' => 'There is some technical error. Please re-try after some time.'
			]);
		}
	}	
}