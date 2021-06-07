<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Log;
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
use DB;

class UserController extends Controller {

    protected $successCode = Res::HTTP_OK;
    protected $badRequestCode = Res::HTTP_BAD_REQUEST;
    protected $serverError = Res::HTTP_INTERNAL_SERVER_ERROR;
    protected $unAuthorized = Res::HTTP_UNAUTHORIZED;

    public function registration(Request $request) {
        /* $validator = Validator::make($request->all(), [
          'first_name' => 'required',
          'last_name' => 'required',
          'email' => 'required|string|email|max:255|unique:users',
          'date_of_birth' => 'required|date|before:18 years ago',
          'age' => 'required|integer|between:18,100',
          'gender' => 'required',
          'password' => 'required|confirmed|min:8',
          ]); */
        /* if ($validator->fails()) {
          return response()->json($validator->errors());
          } */

        /* if($validator->fails()){
          return Response::json(['status' => 'error',
          'status_code' => $this->badRequestCode,
          'message' => 'Invalid Attributes',
          'data' => $validator->errors()]);
          } */

        $response['status'] = $this->badRequestCode;
        $response['message'] = 'Invalid Attributes';

        $fname = $request->get('first_name');
        if (!$fname) {
            $response['error_message'] = 'First Name is required.';
            return Response::json($response);
        }

        $lname = $request->get('last_name');
        if (!$lname) {
            $response['error_message'] = 'Last Name is required.';
            return Response::json($response);
        }

        $email = $request->get('email');
        if (!$email) {
            $response['error_message'] = 'Email is required.';
            return Response::json($response);
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $response['error_message'] = 'Invalid Email.';
            return Response::json($response);
        }

        $emailExists = UserModel::emailExists($request->get('email'));

        if ($emailExists) {
            $response['error_message'] = 'Email has already been taken.';
            return Response::json($response);
        }

        $dob = $request->get('date_of_birth');
        if (!$dob) {
            $response['error_message'] = 'Date Of Birth is required.';
            return Response::json($response);
        }

        $age = $request->get('age');
        if (!$age) {
            $response['error_message'] = 'Age is required.';
            return Response::json($response);
        }

        $gender = $request->get('gender');
        if (!$gender) {
            $response['error_message'] = 'Gender is required.';
            return Response::json($response);
        }

        $password = $request->get('password');
        if (!$password) {
            $response['error_message'] = 'Password is required.';
            return Response::json($response);
        }

        if (strlen($password) < 8) {
            $response['error_message'] = 'Password should be minimum 8 characters length.';
            return Response::json($response);
        }

        $cPassword = $request->get('password_confirmation');
        if (!$cPassword) {
            $response['error_message'] = 'Confirm password is required.';
            return Response::json($response);
        }

        if (strcmp($password, $cPassword)) {
            $response['error_message'] = 'The password confirmation does not match.';
            return Response::json($response);
        }

        $deviceToken = $request->get('device_token');
        if (!$deviceToken) {
            $response['error_message'] = 'Device token is required to send push notifications.';
            return Response::json($response);
        }

        $deviceType = $request->get('device_type');
        if (!$deviceType) {
            $response['error_message'] = 'Device type is required to send push notifications.';
            return Response::json($response);
        }

        $pin = $request->get('pin');
        if (!$pin) {
            $response['error_message'] = 'Pin is required.';
            return Response::json($response);
        }

        if (!is_int($pin)) {
            $response['error_message'] = 'Pin should be numeric only.';
            return Response::json($response);
        }

        if (strlen($pin) != 4) {
            $response['error_message'] = 'Pin should be 4 digit only.';
            return Response::json($response);
        }

        User::create([
            'email' => $request->get('email'),
            'password' => bcrypt($request->get('password')),
            'pin' => bcrypt($request->get('pin')),
        ]);

        $userDetails = UserModel::userDetails($request->get('email'));

        // Get an Encrypter implementation from the service container.
        $encrypter = app('Illuminate\Contracts\Encryption\Encrypter');

        $regData = array(
            'user_id' => $userDetails->id,
            'first_name' => $encrypter->encrypt($request->get('first_name')),
            'last_name' => $encrypter->encrypt($request->get('last_name')),
            'email' => $encrypter->encrypt($request->get('email')),
            'date_of_birth' => $encrypter->encrypt($request->get('date_of_birth')),
            'age' => $encrypter->encrypt($request->get('age')),
            'gender' => $encrypter->encrypt($request->get('gender')),
            'device_token' => $deviceToken ? $deviceToken : '',
            'device_type' => $deviceType ? $deviceType : '',
            'created_at' => date('Y-m-d H:i:s')
        );

        $regInsert = UserModel::registerInsert($regData);

        $user = User::where('id', $userDetails->id)->first();

        $token = JWTAuth::fromUser($user);

        return Response::json([
                    'status' => $this->successCode,
                    'message' => 'Registration success.']);
    }

    public function forgotPassword(Request $request) {

        $response['status'] = $this->badRequestCode;
        $response['message'] = 'Invalid Attributes';

        $email = $request->get('email');
        if (!$email) {
            $response['error_message'] = 'Email is required.';
            return Response::json($response);
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $response['error_message'] = 'Invalid Email.';
            return Response::json($response);
        }

        //$emailExists = UserModel::emailExists($request->get('email'));
        $userDetails = UserModel::userDetails($email);

        if (!$userDetails) {
            return Response::json([
                        'status' => 400,
                        'error_message' => 'Email not exists.'
            ]);
        }

        return Response::json([
                    'status' => $this->successCode,
                    'message' => 'Email exists.',
                    'data' => $userDetails
        ]);
    }

    public function resetPassword(Request $request) {
        $response['status'] = $this->badRequestCode;
        $response['message'] = 'Invalid Attributes';

        $userId = $request->get('user_id');
        if (!$userId) {
            $response['error_message'] = 'User Id is required.';
            return Response::json($response);
        }

        $password = $request->get('password');
        if (!$password) {
            $response['error_message'] = 'Password is required.';
            return Response::json($response);
        }

        if (strlen($password) < 8) {
            $response['error_message'] = 'Password should be minimum 8 characters length.';
            return Response::json($response);
        }

        $cPassword = $request->get('password_confirmation');
        if (!$cPassword) {
            $response['error_message'] = 'Confirm password is required.';
            return Response::json($response);
        }

        if (strcmp($password, $cPassword)) {
            $response['error_message'] = 'The password confirmation does not match.';
            return Response::json($response);
        }

        $pwdUpdate = array(
            'password' => bcrypt($password),
            'updated_at' => date('Y-m-d H:i:s')
        );

        $resUpdate = UserModel::passwordUpdate($pwdUpdate, $userId);

        if (!empty($resUpdate)) {
            return Response::json([
                        'status' => $this->successCode,
                        'message' => 'Password successfully updated.'
            ]);
        } else {
            return Response::json([
                        'status' => $this->serverError,
                        'message' => 'There is some technical error. Please re-try after some time.'
            ]);
        }
    }

    public function resetPin(Request $request) {
        $response['status'] = $this->badRequestCode;
        $response['message'] = 'Invalid Attributes';
        $response['data'] = false;

        $userId = $request->get('user_id');
        if (!$userId) {
            $response['error_message'] = 'User Id is required.';
            return Response::json($response);
        }

        $pin = (int) $request->get('pin');

        if (!$pin) {
            $response['error_message'] = 'Pin is required.';
            return Response::json($response);
        }

        if (!is_int($pin)) {
            $response['error_message'] = 'Pin should be numeric only.';
            return Response::json($response);
        }

        if (strlen($pin) != 4) {
            $response['error_message'] = 'Pin should be 4 digit only.';
            return Response::json($response);
        }

        $cPin = (int) $request->get('pin_confirmation');
        if (!$cPin) {
            $response['error_message'] = 'Confirm pin is required.';
            return Response::json($response);
        }

        if (!is_int($cPin)) {
            $response['error_message'] = 'Confirm pin should be numeric only.';
            return Response::json($response);
        }

        if (strlen($cPin) != 4) {
            $response['error_message'] = 'Confirm pin should be 4 digit only.';
            return Response::json($response);
        }

        if (strcmp($pin, $cPin)) {
            $response['error_message'] = 'The pin confirmation does not match.';
            return Response::json($response);
        }

        $pinUpdate = array(
            'pin' => bcrypt($pin),
            'updated_at' => date('Y-m-d H:i:s')
        );

        $resUpdate = UserModel::passwordUpdate($pinUpdate, $userId);

        if (!empty($resUpdate)) {
            return Response::json([
                        'status' => $this->successCode,
                        'message' => 'Pin successfully updated.',
                        'data' => true
            ]);
        } else {
            return Response::json([
                        'status' => $this->serverError,
                        'message' => 'There is some technical error. Please re-try after some time.'
            ]);
        }
    }

    public function confirmPin(Request $request, $userId) {

        $response['status'] = 400;
        $response['message'] = 'Invalid Attributes.';
        $response['data'] = false;

        if (!$userId) {
            $response['message'] = 'User Id is required.';
            return Response::json($response);
        }

        $userDetails = UserModel::userDetailsById($userId);

        if ($userDetails) {

            if (password_verify($request->get('pin'), $userDetails->pin)) {
//                $response['status'] = 200;
//                $response['message'] = 'Pin matched successfully';
//                $response['data'] = true;
                return Response::json([
                            'status' => $this->successCode,
                            'message' => 'Pin matched successfully',
                            'data' => true
                ]);
            } else {
                $response['message'] = 'Invalid Pin';
            }
        }

        return Response::json($response);
    }

    public function profile(Request $request, $userId) {

        $response['status'] = 400;
        $response['message'] = 'Invalid Attributes.';

        if (!$userId) {
            $response['error_message'] = 'User Id is required.';
            return Response::json($response);
        }

        $userDetails = UserModel::profile($userId);

        if (!$userDetails)
            $this->successCode = 400;

        return Response::json([
                    'status' => $this->successCode,
                    'message' => 'User profile details.',
                    'data' => $userDetails
        ]);
    }

    public function profileUpdate(Request $request) {

        /* $validator = Validator::make($request->all(), [
          'first_name' => 'required',
          'last_name' => 'required',
          'date_of_birth' => 'required|date|before:18 years ago',
          'age' => 'required|integer|between:18,100',
          'gender' => 'required',
          ]);

          if ($validator->fails()) {
          return response()->json($validator->errors());
          } */

        $response['status'] = 400;
        $response['message'] = 'Invalid Attributes';

        $userId = $request->get('user_id');
        if (!$userId) {
            $response['error_message'] = 'User Id is required.';
            return Response::json($response);
        }

        $fname = $request->get('first_name');
        if (!$fname) {
            $response['error_message'] = 'First Name is required.';
            return Response::json($response);
        }

        $lname = $request->get('last_name');
        if (!$lname) {
            $response['error_message'] = 'Last Name is required.';
            return Response::json($response);
        }

        $dob = $request->get('date_of_birth');
        if (!$dob) {
            $response['error_message'] = 'Date Of Birth is required.';
            return Response::json($response);
        }

        $age = $request->get('age');
        if (!$age) {
            $response['error_message'] = 'Age is required.';
            return Response::json($response);
        }

        $gender = $request->get('gender');
        if (!$gender) {
            $response['error_message'] = 'Gender is required.';
            return Response::json($response);
        }

        /* $profUpdate = array(
          'first_name' => $request->get('first_name'),
          'last_name' => $request->get('last_name'),
          'date_of_birth' => $request->get('date_of_birth'),
          'age' => $request->get('age'),
          'gender' => $request->get('gender'),
          'updated_at' => date('Y-m-d H:i:s')
          ); */

        $userDetails = UserModel::userDetails($request->get('email'));

        // Get an Encrypter implementation from the service container.
        $encrypter = app('Illuminate\Contracts\Encryption\Encrypter');

        $profUpdate = array(
            'first_name' => $encrypter->encrypt($request->get('first_name')),
            'last_name' => $encrypter->encrypt($request->get('last_name')),
            'date_of_birth' => $encrypter->encrypt($request->get('date_of_birth')),
            'age' => $encrypter->encrypt($request->get('age')),
            'gender' => $encrypter->encrypt($request->get('gender')),
            'updated_at' => date('Y-m-d H:i:s')
        );

        $regUpdate = UserModel::registerUpdate($profUpdate, $userId);

        if (!empty($regUpdate)) {
            $userDetails = UserModel::profile($userId);
            return Response::json(['status' => $this->successCode,
                        'message' => 'Profile successfully updated.',
                        'data' => $userDetails]);
        } else {
            return Response::json([
                        'status' => $this->serverError,
                        'message' => 'There is some technical error. Please re-try after some time.'
            ]);
        }
    }

    public function login(Request $request) {
        /* $validator = Validator::make($request->all(), [
          'email' => 'required|string|email|max:255',
          'password'=> 'required|min:8'
          ]); */

        /* if ($validator->fails()) {
          return response()->json($validator->errors());
          } */

        /* if($validator->fails()){
          return Response::json(['status' => $this->badRequestCode,
          'message' => 'Invalid attributes',
          'data' => $validator->errors()]);
          } */


        $response['status'] = 400;
        $response['message'] = 'Invalid Attributes';

        $email = $request->get('email');
        if (!$email) {
            $response['error_message'] = 'Email is required.';
            return Response::json($response);
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $response['error_message'] = 'Invalid Email.';
            return Response::json($response);
        }

        $password = $request->get('password');
        if (!$password) {
            $response['error_message'] = 'Password is required.';
            return Response::json($response);
        }

        if (strlen($password) < 8) {
            $response['error_message'] = 'Password should be minimum 8 characters length.';
            return Response::json($response);
        }

        $deviceToken = $request->get('device_token');
        /* if(!$deviceToken) {
          $response['error_message'] = 'Device token is required to send push notifications.';
          return Response::json($response);
          } */

        $deviceType = $request->get('device_type');
        /* if(!$deviceType) {
          $response['error_message'] = 'Device type is required to send push notifications.';
          return Response::json($response);
          } */

        $credentials = $request->only('email', 'password');
        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json([
                            'status' => $this->unAuthorized,
                            'message' => 'Login Failed.',
                            'error_message' => 'Incorrect username/password. Please retry with valid credentials.'
                ]);
            }
        } catch (JWTException $e) {
            return response()->json([
                        'status' => $this->serverError,
                        'message' => 'Could not create token.',
                        'error_message' => 'There is some technical error. Please re-try after some time.'
            ]);
        }
        $userDetails = UserModel::userDetails($request->get('email'));

        Log::info('User logged in.', ['id' => $userDetails->id]);

        $userProfile = UserModel::profile($userDetails->id);

        //To send the value of viewed_tnc, it contains 1 if terms and conditions are read
        $userDetails->viewed_tnc = $userProfile['viewed_tnc'];

        if ($userProfile['is_deleted'] == '1') {
            $response['error_message'] = 'Your account has been deleted by admin';
            return Response::json($response);
        }

        //To update device token and device type
        $profUpdate = array(
            'device_token' => $deviceToken ? $deviceToken : '',
            'device_type' => $deviceType ? $deviceType : '',
            'updated_at' => date('Y-m-d H:i:s')
        );

        $regUpdate = UserModel::registerUpdate($profUpdate, $userDetails->id);

        return response()->json([
                    'status' => $this->successCode,
                    'message' => 'Login successful.',
                    'data' => $userDetails,
                    'token' => compact('token')]);
    }

    public function logout(Request $request) {
        $response['status'] = 400;
        $response['message'] = 'Invalid Attributes';

        $userId = $request->get('user_id');
        if (!$userId) {
            $response['error_message'] = 'User Id is required.';
            return Response::json($response);
        }

        //To update device token and device type to empty
        $profUpdate = array(
            'device_token' => '',
            'device_type' => ''
        );

        $regUpdate = UserModel::registerUpdate($profUpdate, $userId);

        JWTAuth::invalidate();

        return response()->json(['status' => $this->successCode,
                    'message' => 'Logout successful.']);
    }

    public function updateDeviceToken(Request $request) {
        $response['status'] = 400;
        $response['message'] = 'Invalid Attributes';

        $userId = $request->get('user_id');
        if (!$userId) {
            $response['error_message'] = 'User Id is required.';
            return Response::json($response);
        }

        $deviceToken = $request->get('device_token');
        if (!$deviceToken) {
            $response['error_message'] = 'Device token is required to send push notifications.';
            return Response::json($response);
        }

        $deviceType = $request->get('device_type');
        if (!$deviceType) {
            $response['error_message'] = 'Device type is required to send push notifications.';
            return Response::json($response);
        }

        $profUpdate = array(
            'device_token' => $deviceToken ? $deviceToken : '',
            'device_type' => $deviceType ? $deviceType : '',
            'updated_at' => date('Y-m-d H:i:s')
        );

        $regUpdate = UserModel::registerUpdate($profUpdate, $userId);

        if (!empty($regUpdate)) {
            return Response::json(['status' => $this->successCode,
                        'message' => 'Device token successfully updated.',
            ]);
        } else {
            return Response::json([
                        'status' => $this->serverError,
                        'message' => 'There is some technical error. Please re-try after some time.'
            ]);
        }
    }

    public function leaderBoardDaily(Request $request) {
        $userId = $request->get('user_id');
        $date = $request->get('date');

        $response['status'] = 400;
        $response['message'] = 'Invalid Attributes';

        $userId = $request->get('user_id');
        if (!$userId) {
            $response['error_message'] = 'User Id is required.';
            return Response::json($response);
        }

        $urinalVisits = UserModel::symptomsDaily('urinal_visits', $userId, $date);
        $lossBladderControl = UserModel::symptomsDaily('loss_bladder_control', $userId, $date);
        $nocturia = UserModel::symptomsDaily('nocturia', $userId, $date);
        $urinaryHesitancy = UserModel::symptomsDaily('urinary_hesitancy', $userId, $date);
        $urinaryUrgency = UserModel::symptomsDaily('urinary_urgency', $userId, $date);

        $symptomsDailyCount = array('user_id' => $userId,
            'date' => $date,
            'urinal_visits' => $urinalVisits,
            'loss_bladder_control' => $lossBladderControl,
            'nocturia' => $nocturia,
            'urinary_hesitancy' => $urinaryHesitancy,
            'urinary_urgency' => $urinaryUrgency);

        return Response::json([
                    'status' => $this->successCode,
                    'message' => 'Leader Board Daily Data.',
                    'data' => $symptomsDailyCount]);
    }

    public function leaderBoardWeekly(Request $request) {
        $userId = $request->get('user_id');
        $year = $request->get('year');
        $month = $request->get('month');

        $response['status'] = 400;
        $response['message'] = 'Invalid Attributes';

        if (!$userId) {
            $response['error_message'] = 'User Id is required.';
            return Response::json($response);
        }

        if (!$year) {
            $response['error_message'] = 'Year is required.';
            return Response::json($response);
        }

        if (!$month) {
            $response['error_message'] = 'Month is required.';
            return Response::json($response);
        }

        $arrWeeks = array(1, 2, 3, 4, 5);
        $arrWeeklyData = array();
        foreach ($arrWeeks as $week) {
            $arrWeeklyData[$week]['week'] = $week;
            $arrWeeklyData[$week]['urinal_visits'] = '0.0000';
            $arrWeeklyData[$week]['loss_bladder_control'] = '0.0000';
            $arrWeeklyData[$week]['nocturia'] = '0.0000';
            $arrWeeklyData[$week]['urinary_hesitancy'] = '0.0000';
            $arrWeeklyData[$week]['urinary_urgency'] = '0.0000';
        }

        $urinalVisits = UserModel::symptomsWeekly('urinal_visits', $userId, $year, $month);
        $lossBladderControl = UserModel::symptomsWeekly('loss_bladder_control', $userId, $year, $month);
        $nocturia = UserModel::symptomsWeekly('nocturia', $userId, $year, $month);
        $urinaryHesitancy = UserModel::symptomsWeekly('urinary_hesitancy', $userId, $year, $month);
        $urinaryUrgency = UserModel::symptomsWeekly('urinary_urgency', $userId, $year, $month);

        foreach ($urinalVisits as $urinalVisitsOne) {
            $arrWeeklyData[$urinalVisitsOne->week_number]['urinal_visits'] = (string) round($urinalVisitsOne->avg, 2);
        }

        foreach ($lossBladderControl as $lossBladderControlOne) {
            $arrWeeklyData[$lossBladderControlOne->week_number]['loss_bladder_control'] = (string) round($lossBladderControlOne->avg, 2);
        }

        foreach ($nocturia as $nocturiaOne) {
            $arrWeeklyData[$nocturiaOne->week_number]['nocturia'] = (string) round($nocturiaOne->avg, 2);
        }

        foreach ($urinaryHesitancy as $urinaryHesitancyOne) {
            $arrWeeklyData[$urinaryHesitancyOne->week_number]['urinary_hesitancy'] = (string) round($urinaryHesitancyOne->avg, 2);
        }

        foreach ($urinaryUrgency as $urinaryUrgencyOne) {
            $arrWeeklyData[$urinaryUrgencyOne->week_number]['urinary_urgency'] = (string) round($urinaryUrgencyOne->avg, 2);
        }

        return Response::json([
                    'status' => $this->successCode,
                    'message' => 'Leader Board Weekly Data.',
                    'data' => array_values($arrWeeklyData)]);
    }

    public function leaderBoardMonthly(Request $request) {
        $userId = $request->get('user_id');
        $year = $request->get('year');

        $response['status'] = 400;
        $response['message'] = 'Invalid Attributes';

        if (!$userId) {
            $response['error_message'] = 'User Id is required.';
            return Response::json($response);
        }

        if (!$year) {
            $response['error_message'] = 'Year is required.';
            return Response::json($response);
        }

        $arrMonths = array('1' => 31, '2' => 28, '3' => 31, '4' => 30, '5' => 31,
            '6' => 30, '7' => 31, '8' => 31, '9' => 30, '10' => 31, '11' => 30, '12' => 31);
        $arrMonthlyData = array();
        foreach ($arrMonths as $month => $days) {
            $arrMonthlyData[$month]['month'] = (string) $month;
            $arrMonthlyData[$month]['urinal_visits'] = '0.0000';
            $arrMonthlyData[$month]['loss_bladder_control'] = '0.0000';
            $arrMonthlyData[$month]['nocturia'] = '0.0000';
            $arrMonthlyData[$month]['urinary_hesitancy'] = '0.0000';
            $arrMonthlyData[$month]['urinary_urgency'] = '0.0000';
        }

        $urinalVisits = UserModel::symptomsMonthly('urinal_visits', $userId, $year);
        $lossBladderControl = UserModel::symptomsMonthly('loss_bladder_control', $userId, $year);
        $nocturia = UserModel::symptomsMonthly('nocturia', $userId, $year);
        $urinaryHesitancy = UserModel::symptomsMonthly('urinary_hesitancy', $userId, $year);
        $urinaryUrgency = UserModel::symptomsMonthly('urinary_urgency', $userId, $year);

        foreach ($urinalVisits as $urinalVisitsOne) {
            $noOfDays = $arrMonths[$urinalVisitsOne->month];
            $arrMonthlyData[$urinalVisitsOne->month]['urinal_visits'] = (string) round($urinalVisitsOne->sum / $noOfDays, 2);
        }

        foreach ($lossBladderControl as $lossBladderControlOne) {
            $noOfDays = $arrMonths[$lossBladderControlOne->month];
            $arrMonthlyData[$lossBladderControlOne->month]['loss_bladder_control'] = (string) round($lossBladderControlOne->sum / $noOfDays, 2);
        }

        foreach ($nocturia as $nocturiaOne) {
            $noOfDays = $arrMonths[$nocturiaOne->month];
            $arrMonthlyData[$nocturiaOne->month]['nocturia'] = (string) round($nocturiaOne->sum / $noOfDays, 2);
        }

        foreach ($urinaryHesitancy as $urinaryHesitancyOne) {
            $noOfDays = $arrMonths[$urinaryHesitancyOne->month];
            $arrMonthlyData[$urinaryHesitancyOne->month]['urinary_hesitancy'] = (string) round($urinaryHesitancyOne->sum / $noOfDays, 2);
        }

        foreach ($urinaryUrgency as $urinaryUrgencyOne) {
            $noOfDays = $arrMonths[$urinaryUrgencyOne->month];
            $arrMonthlyData[$urinaryUrgencyOne->month]['urinary_urgency'] = (string) round($urinaryUrgencyOne->sum / $noOfDays, 2);
        }

        return Response::json([
                    'status' => $this->successCode,
                    'message' => 'Leader Board Monthly Data.',
                    'data' => array_values($arrMonthlyData)]);
    }

    public function symptomSave(Request $request) {
        $response['status'] = $this->badRequestCode;
        $response['message'] = 'Invalid Attributes';

        $userId = $request->get('user_id');
        if (!$userId) {
            $response['error_message'] = 'User Id is required.';
            return Response::json($response);
        }

        $symptom = $request->get('symptom');
        if (!$symptom) {
            $response['error_message'] = 'Symptom is required.';
            return Response::json($response);
        }

        $time = $request->get('time');
        if (!$time) {
            $response['error_message'] = 'Time is required.';
            return Response::json($response);
        }

        $severity = $request->get('severity');
        if (!$severity) {
            $response['error_message'] = 'Severity is required.';
            return Response::json($response);
        }

        $duration = $request->get('duration');
        if (!$duration) {
            $response['error_message'] = 'Duration is required.';
            return Response::json($response);
        }

        $clientDateTime = $request->get('client_datetime');
        if (!$clientDateTime) {
            $response['error_message'] = 'Client Date Time is required.';
            return Response::json($response);
        }

        $clientTimeZone = $request->get('client_timezone');
        if (!$clientTimeZone) {
            $response['error_message'] = 'Client Time Zone is required.';
            return Response::json($response);
        }

        $clientTimeZoneOffset = $request->get('client_timezone_offset');
        if (!$clientTimeZoneOffset) {
            $response['error_message'] = 'Client Time Zone Offset is required.';
            return Response::json($response);
        }

        $symptomData = array(
            'user_id' => $userId,
            'time' => $time,
            'severity' => $severity,
            'duration' => $duration,
            'client_datetime' => $clientDateTime,
            'client_timezone' => $clientTimeZone,
            'client_timezone_offset' => $clientTimeZoneOffset
        );

        $regInsert = UserModel::symptomInsert($symptom, $symptomData);

        return Response::json([
                    'status' => $this->successCode,
                    'message' => 'Data has been successfully inserted.']);
    }

    public function symptomData(Request $request) {

        $response['status'] = 400;
        $response['message'] = 'Invalid Attributes.';

        $userId = $request->get('user_id');
        if (!$userId) {
            $response['error_message'] = 'User Id is required.';
            return Response::json($response);
        }

        $symptom = $request->get('symptom');
        if (!$symptom) {
            $response['error_message'] = 'Symptom is required.';
            return Response::json($response);
        }

        $date = $request->get('date');
        if (!$date) {
            $response['error_message'] = 'Date is required.';
            return Response::json($response);
        }

        $symptomDetails = UserModel::symptomData($symptom, $userId, $date);

        if (!count($symptomDetails))
            $this->successCode = 400;

        return Response::json([
                    'status' => $this->successCode,
                    'message' => 'Symptom details.',
                    'data' => $symptomDetails
        ]);
    }

    public function medicationSave(Request $request) {
        $response['status'] = 400;
        $response['message'] = 'Invalid Attributes.';

        $userId = $request->get('user_id');
        if (!$userId) {
            $response['error_message'] = 'User Id is required.';
            return Response::json($response);
        }

        $drugName = $request->get('drug_name');
        if (!$drugName) {
            $response['error_message'] = 'Drug name is required.';
            return Response::json($response);
        }

        $qty = $request->get('qty');
        if (!$qty) {
            $response['error_message'] = 'Quantity is required.';
            return Response::json($response);
        }

        $dosage = $request->get('dosage');
        if (!$dosage) {
            $response['error_message'] = 'Dosage is required.';
            return Response::json($response);
        }

        $time = $request->get('time');
        if (!$time) {
            $response['error_message'] = 'Time is required.';
            return Response::json($response);
        }

        $clientDateTime = $request->get('client_datetime');
        if (!$clientDateTime) {
            $response['error_message'] = 'Client Date Time is required.';
            return Response::json($response);
        }

        $clientTimeZone = $request->get('client_timezone');
        if (!$clientTimeZone) {
            $response['error_message'] = 'Client Time Zone is required.';
            return Response::json($response);
        }

        $clientTimeZoneOffset = $request->get('client_timezone_offset');
        if (!$clientTimeZoneOffset) {
            $response['error_message'] = 'Client Time Zone Offset is required.';
            return Response::json($response);
        }

        $medicationData = array(
            'user_id' => $userId,
            'drug_name' => $drugName,
            'qty' => $qty,
            'dosage' => $dosage,
            'time' => $time,
            'client_datetime' => $clientDateTime,
            'client_timezone' => $clientTimeZone,
            'client_timezone_offset' => $clientTimeZoneOffset
        );

        $medicationIns = UserModel::medicationInsert($medicationData);

        return Response::json([
                    'status' => $this->successCode,
                    'message' => 'Data has been successfully inserted.']);
    }

    public function medicationData(Request $request) {

        $response['status'] = 400;
        $response['message'] = 'Invalid Attributes.';

        $userId = $request->get('user_id');
        if (!$userId) {
            $response['error_message'] = 'User Id is required.';
            return Response::json($response);
        }

        $date = $request->get('date');
        if (!$date) {
            $response['error_message'] = 'Date is required.';
            return Response::json($response);
        }

        $medicationDetails = UserModel::medicationData($userId, $date);

        if (!count($medicationDetails))
            $this->successCode = 400;

        return Response::json([
                    'status' => $this->successCode,
                    'message' => 'Medication details.',
                    'data' => $medicationDetails
        ]);
    }

    public function dietSave(Request $request) {
        $response['status'] = $this->badRequestCode;
        $response['message'] = 'Invalid Attributes';

        $userId = $request->get('user_id');
        if (!$userId) {
            $response['error_message'] = 'User Id is required.';
            return Response::json($response);
        }

        $description = $request->get('description');
        if (!$description) {
            $response['error_message'] = 'Description is required.';
            return Response::json($response);
        }

        $qty = $request->get('qty');
        if (!$qty) {
            $response['error_message'] = 'Quantity is required.';
            return Response::json($response);
        }

        $type = $request->get('type');
        if (!$type) {
            $response['error_message'] = 'Type is required.';
            return Response::json($response);
        }

        $clientDateTime = $request->get('client_datetime');
        if (!$clientDateTime) {
            $response['error_message'] = 'Client Date Time is required.';
            return Response::json($response);
        }

        $clientTimeZone = $request->get('client_timezone');
        if (!$clientTimeZone) {
            $response['error_message'] = 'Client Time Zone is required.';
            return Response::json($response);
        }

        $clientTimeZoneOffset = $request->get('client_timezone_offset');
        if (!$clientTimeZoneOffset) {
            $response['error_message'] = 'Client Time Zone Offset is required.';
            return Response::json($response);
        }

        $dietData = array(
            'user_id' => $userId,
            'desc' => $description,
            'qty' => $qty,
            'type' => $type,
            'client_datetime' => $clientDateTime,
            'client_timezone' => $clientTimeZone,
            'client_timezone_offset' => $clientTimeZoneOffset
        );

        $dietInsert = UserModel::dietInsert($dietData);

        return Response::json([
                    'status' => $this->successCode,
                    'message' => 'Data has been successfully inserted.']);
    }

    public function dietData(Request $request) {

        $response['status'] = 400;
        $response['message'] = 'Invalid Attributes.';

        $userId = $request->get('user_id');
        if (!$userId) {
            $response['error_message'] = 'User Id is required.';
            return Response::json($response);
        }

        $date = $request->get('date');
        if (!$date) {
            $response['error_message'] = 'Date is required.';
            return Response::json($response);
        }

        $type = $request->get('type');
        if (!$type) {
            $response['error_message'] = 'Type is required.';
            return Response::json($response);
        }

        $dietDetails = UserModel::dietData($userId, $date, $type);

        if (!count($dietDetails))
            $this->successCode = 400;

        return Response::json([
                    'status' => $this->successCode,
                    'message' => 'Diet details.',
                    'data' => $dietDetails
        ]);
    }

    public function moodSave(Request $request) {
        $response['status'] = $this->badRequestCode;
        $response['message'] = 'Invalid Attributes';

        $userId = $request->get('user_id');
        if (!$userId) {
            $response['error_message'] = 'User Id is required.';
            return Response::json($response);
        }

        $message = $request->get('message');
        if (!$message) {
            $response['error_message'] = 'Message is required.';
            return Response::json($response);
        }

        $emojiCode = $request->get('emoji_code');
        if (!$emojiCode) {
            $response['error_message'] = 'Emoji code is required.';
            return Response::json($response);
        }

        $clientDateTime = $request->get('client_datetime');
        if (!$clientDateTime) {
            $response['error_message'] = 'Client Date Time is required.';
            return Response::json($response);
        }

        $clientTimeZone = $request->get('client_timezone');
        if (!$clientTimeZone) {
            $response['error_message'] = 'Client Time Zone is required.';
            return Response::json($response);
        }

        $clientTimeZoneOffset = $request->get('client_timezone_offset');
        if (!$clientTimeZoneOffset) {
            $response['error_message'] = 'Client Time Zone Offset is required.';
            return Response::json($response);
        }

        $moodData = array(
            'user_id' => $userId,
            'message' => $message,
            'emoji_code' => $emojiCode,
            'client_datetime' => $clientDateTime,
            'client_timezone' => $clientTimeZone,
            'client_timezone_offset' => $clientTimeZoneOffset
        );

        $moodInsert = UserModel::moodInsert($moodData);

        return Response::json([
                    'status' => $this->successCode,
                    'message' => 'Data has been successfully inserted.']);
    }

    public function moodData(Request $request) {

        $response['status'] = 400;
        $response['message'] = 'Invalid Attributes.';

        $userId = $request->get('user_id');
        if (!$userId) {
            $response['error_message'] = 'User Id is required.';
            return Response::json($response);
        }

        $date = $request->get('date');
        if (!$date) {
            $response['error_message'] = 'Date is required.';
            return Response::json($response);
        }

        $moodDetails = UserModel::moodData($userId, $date);

        if (!count($moodDetails))
            $this->successCode = 400;

        return Response::json([
                    'status' => $this->successCode,
                    'message' => 'Mood details.',
                    'data' => $moodDetails
        ]);
    }

    public function activitySave(Request $request) {
        $response['status'] = $this->badRequestCode;
        $response['message'] = 'Invalid Attributes';

        $userId = $request->get('user_id');
        if (!$userId) {
            $response['error_message'] = 'User Id is required.';
            return Response::json($response);
        }

        $name = $request->get('name');
        if (!$name) {
            $response['error_message'] = 'Name is required.';
            return Response::json($response);
        }

        $description = $request->get('description');
        if (!$description) {
            $response['error_message'] = 'Description is required.';
            return Response::json($response);
        }

        $startTime = $request->get('start_time');
        if (!$startTime) {
            $response['error_message'] = 'Start time is required.';
            return Response::json($response);
        }

        /* $endTime = $request->get('end_time');
          if(!$endTime) {
          $response['error_message'] = 'End time is required.';
          return Response::json($response);
          } */

        $clientDateTime = $request->get('client_datetime');
        if (!$clientDateTime) {
            $response['error_message'] = 'Client Date Time is required.';
            return Response::json($response);
        }

        $clientTimeZone = $request->get('client_timezone');
        if (!$clientTimeZone) {
            $response['error_message'] = 'Client Time Zone is required.';
            return Response::json($response);
        }

        $clientTimeZoneOffset = $request->get('client_timezone_offset');
        if (!$clientTimeZoneOffset) {
            $response['error_message'] = 'Client Time Zone Offset is required.';
            return Response::json($response);
        }

        $activityData = array(
            'user_id' => $userId,
            'name' => $name,
            'desc' => $description,
            'start_time' => $startTime,
            //'end_time' => $endTime,
            'client_datetime' => $clientDateTime,
            'client_timezone' => $clientTimeZone,
            'client_timezone_offset' => $clientTimeZoneOffset
        );

        $activityInsert = UserModel::activityInsert($activityData);

        return Response::json([
                    'status' => $this->successCode,
                    'message' => 'Data has been successfully inserted.']);
    }

    public function activityData(Request $request) {

        $response['status'] = 400;
        $response['message'] = 'Invalid Attributes.';

        $userId = $request->get('user_id');
        if (!$userId) {
            $response['error_message'] = 'User Id is required.';
            return Response::json($response);
        }

        $date = $request->get('date');
        if (!$date) {
            $response['error_message'] = 'Date is required.';
            return Response::json($response);
        }

        $activityDetails = UserModel::activityData($userId, $date);

        if (!count($activityDetails))
            $this->successCode = 400;

        return Response::json([
                    'status' => $this->successCode,
                    'message' => 'Activity details.',
                    'data' => $activityDetails
        ]);
    }

    public function viewedTnC(Request $request) {
        $response['status'] = 400;
        $response['message'] = 'Invalid Attributes';

        $userId = $request->get('user_id');
        if (!$userId) {
            $response['error_message'] = 'User Id is required.';
            return Response::json($response);
        }

        $viewedUpdate = array(
            'viewed_tnc' => 1,
            'updated_at' => date('Y-m-d H:i:s')
        );

        $resUpdate = UserModel::registerUpdate($viewedUpdate, $userId);

        if (!empty($resUpdate)) {
            return Response::json(['status' => $this->successCode,
                        'message' => 'Sucessfully updated viewed TnC flag.',
            ]);
        } else {
            return Response::json([
                        'status' => $this->serverError,
                        'message' => 'There is some technical error. Please re-try after some time.'
            ]);
        }
    }

    public function aboutUs() {
        $response['status'] = 400;
        $response['message'] = 'Invalid Attributes';

        $aboutUs = UserModel::aboutUsContent();
        if (!empty($aboutUs)) {
            return Response::json([
                        'status' => $this->successCode,
                        'data' => $aboutUs[0]
            ]);
        } else {
            return Response::json([
                        'status' => $this->serverError,
                        'message' => 'There is some technical error. Please re-try after some time.'
            ]);
        }
    }

    public function encryptExistingRegistrations() {
        $registrationData = DB::table('registration')->get();

        foreach ($registrationData as $registration) {
            // Get an Encrypter implementation from the service container.
            $encrypter = app('Illuminate\Contracts\Encryption\Encrypter');

            $profUpdate = array(
                'first_name' => $encrypter->encrypt($registration->first_name),
                'last_name' => $encrypter->encrypt($registration->last_name),
                'date_of_birth' => $encrypter->encrypt($registration->date_of_birth),
                'age' => $encrypter->encrypt($registration->age),
                'gender' => $encrypter->encrypt($registration->gender)
                    //'updated_at' => date('Y-m-d H:i:s')
            );

            //if($registration-> user_id < 31) {
            $regUpdate = UserModel::registerUpdate($profUpdate, $registration->user_id);

            if (!empty($regUpdate)) {
                $userDetails = UserModel::profile($registration->user_id);
                print_r($userDetails);
            } else {
                return Response::json([
                            'status' => $this->serverError,
                            'message' => 'There is some technical error. Please re-try after some time.'
                ]);
            }
            //}
        }
    }

}
