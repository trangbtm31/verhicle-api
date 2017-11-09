<?php
/**
 * Created by PhpStorm.
 * User: trang
 * Date: 9/16/17
 * Time: 3:50 PM
 */

namespace App\Http\Controllers;

use App\DeviceInfo;
use App\Requests;
//use App\FCMService;
use LaravelFCM\Message\OptionsBuilder;
use LaravelFCM\Message\PayloadDataBuilder;
use LaravelFCM\Message\PayloadNotificationBuilder;
use LaravelFCM\Sender\FCMSender;
use FCM;

use Illuminate\Http\Request;


class RequestController extends Controller
{
    protected $userId;

    /**
     * RequestController constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->userId = $request->get('user_id');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function pushInfomation(Request $request)
    {
        $requestInfo = new Requests();
        $fcmService = new DeviceInfo();
        $result = array();
        $timeStart = date("h:i", strtotime($request->get('time_start')));
        $vehicleType = $request->get('vehicle_type');
        $userId = $this->userId;
        $fcmToken = $request->get('device_token');
        $deviceId = $request->get('device_id');

        $isOnwer = $requestInfo->where('user_id', '=', $userId)->first();
        $isExistRequest = $requestInfo->where('user_id', '=', $userId)->where('delete_at', '!=', null)->first();
        /*if (empty($isExistRequest) && !empty($isOnwer)) {
            return $this->error(
                1,
                "Transaction is not yet completed",
                200
            );
        }*/
        $requestInfo->create(
            [
                'user_id' => $userId,
                'source_location' => $request->get('source_location'),
                'destination_location' => $request->get('destination_location'),
                'time_start' => $timeStart,
                'vehicle_type' => $vehicleType,
                'status' => 1,
            ]
        );
        $isExistDeviceInfo = $fcmService->where('user_id', '=', $userId)->first();
        if (!$isExistDeviceInfo) {
            $isExistToken = $fcmService
                ->where(
                    [
                        ['token', '=', $fcmToken],
                        ['user_id', '=', $userId],
                    ]
                )
                ->first();
            $isExistDeviceId = $fcmService
                ->where(
                    [
                        ['device_id', '=', $deviceId],
                        ['user_id', '=', $userId],
                    ]
                )
                ->first();
            if (!$isExistToken) {
                $fcmService->create(
                    [
                        'user_id' => $userId,
                        'token' => $fcmToken,
                    ]
                );
            }
            if (!$isExistDeviceId) {
                $isExistDeviceInfo = $deviceId;
                $isExistDeviceInfo->save();
            }
        }
        $activeUsers = $this->getActiveUser($vehicleType, $userId);
        foreach ($activeUsers as $activeUser) {
            array_push(
                $result,
                [
                    "user_info" => [
                        "id" => $activeUser->id,
                        "phone" => $activeUser->phone,
                        "email" => $activeUser->email,
                        "name" => $activeUser->name,
                        "address" => $activeUser->address,
                        "gender" => $activeUser->gender,
                        "birthday" => $activeUser->birthday,
                        "avatar_link" => $activeUser->avatar_link,
                    ],
                    "request_info" => [
                        "vehicle_type" => $activeUser->vehicle_type,
                        "source_location" => json_decode($activeUser->source_location),
                        "dest_location" => json_decode($activeUser->destination_location),
                        "time_start" => $activeUser->time_start,
                    ],
                ]
            );
        }

        return $this->success(
            "active_users",
            $result,
            200
        );

    }

    public function sendRequestToAnotherOne(Request $request)
    {
        $optionBuilder = new OptionsBuilder();
        $fcmService = new DeviceInfo();
        $optionBuilder->setTimeToLive(60*10);

        $notificationBuilder = new PayloadNotificationBuilder('You have a request!');
        $notificationBuilder->setBody('Hey, would you like to go together?')
            ->setSound('default');

        $dataBuilder = new PayloadDataBuilder();
        $dataBuilder->addData([
            'user_id' => $request->get('start_user_id'),
            'user_name' => $request->get('user_name'),
            'start_location' => $request->get('start_location'),
            'end_location' => $request->get('end_location'),
            'start_time' => $request->get('start_time'),
            'vehicle_type' => $request->get('vehicle_type'),
        ]);

        $option = $optionBuilder->build();
        $notification = $notificationBuilder->build();
        $data = $dataBuilder->build();

        $tokenInfo = $fcmService->select('token')->where('user_id','=',$request->get('end_user_id'))->first();
        $downstreamResponse = FCM::sendTo($tokenInfo->token, $option, $notification);
		return $this->success(
			'token',
			$downstreamResponse->numberFailure(),
			200
		);

    }

    /**
     * @param $vehicleType
     */
    private function getActiveUser($vehicleType, $userId)
    {
        $requestInfo = new Requests();
        if ($vehicleType == 0) {
            $activeUser = $requestInfo->join('users', 'requests.user_id', '=', 'users.id')
                ->select(
                    'users.id',
                    'users.phone',
                    'users.email',
                    'users.name',
                    'users.address',
                    'users.gender',
                    'users.birthday',
                    'users.avatar_link',
                    'requests.vehicle_type',
                    'requests.source_location',
                    'requests.destination_location',
                    'requests.time_start'

                )
                ->where('requests.vehicle_type', '!=', '0')->where('requests.user_id', '!=', $userId)->get();
        } else {
            $activeUser = $requestInfo->join('users', 'requests.user_id', '=', 'users.id')
                ->select(
                    'users.id',
                    'users.phone',
                    'users.email',
                    'users.name',
                    'users.address',
                    'users.gender',
                    'users.birthday',
                    'users.avatar_link',
                    'requests.vehicle_type',
                    'requests.source_location',
                    'requests.destination_location',
                    'requests.time_start'

                )
                ->where('requests.vehicle_type', '=', '0')->where('requests.user_id', '!=', $userId)->get();
        }

        return json_decode($activeUser);
    }
}