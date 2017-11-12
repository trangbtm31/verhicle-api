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
use FCM;

use Illuminate\Http\Request;


class RequestController extends Controller
{
	/**
	 * @var user
	 */

	protected $user;

	/**
	 * RequestController constructor.
	 * @param Request $request
	 */
	public function __construct(Request $request)
	{

		$this->user = $request->user();
	}

	/**
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function pushInfomation(Request $request)
	{
		$requestInfo = new Requests();
		$fcmService = new DeviceInfo();
		$user = $request->user();
		$result = array();
		$timeStart = date("h:i", strtotime($request->get('time_start')));
		$vehicleType = $request->get('vehicle_type');
		$userId = $user->id;
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
			if (!$isExistDeviceId && $deviceId) {
				$isExistDeviceInfo->device_id = $deviceId;
				$isExistDeviceInfo->save();
			}
		}
		$activeUsers = $this->getUserRequest( $userId, $vehicleType);
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

	/**
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 * @throws \LaravelFCM\Message\InvalidOptionException
	 */
	public function sendRequestToAnotherOne(Request $request)
	{
		$optionBuilder = new OptionsBuilder();
		$fcmService = new DeviceInfo();
		$user = $this->user;
		$userId = $user->id;
		$optionBuilder->setTimeToLive(60 * 5);

		$notificationBuilder = new PayloadNotificationBuilder('You have a request!');
		$notificationBuilder->setBody('Hey, would you like to go together?')
			->setSound('default');

		$userRequest = $this->getUserRequest($userId);
		$dataBuilder = new PayloadDataBuilder();
		$dataBuilder->addData(
			[
				'user_id' => $userId,
				'user_name' => $userRequest->name,
				'start_location' => $userRequest->source_location,
				'end_location' => $userRequest->destination_location,
				'avatar_link' => $userRequest->avatar_link,
				'start_time' => $userRequest->time_start,
				'vehicle_type' => $userRequest->vehicle_type,
				'note' => $request->get('note'),
			]
		);

		$option = $optionBuilder->build();
		$notification = $notificationBuilder->build();
		$data = $dataBuilder->build();
		$tokenInfo = $fcmService->select('token')->where('user_id', '=', $request->get('receiver_id'))->first();
		$downstreamResponse = FCM::sendTo($tokenInfo->token, $option, $notification, $data);

		return $this->success(
			"FCM_info",
			[
				'data' => $data->toArray(),
				'token_success_number' => $downstreamResponse->numberSuccess(),
			],
			200
		);

	}

	public function acceptRequest(Request $request)
	{
		$user = $this->user;

	}

	/**
	 * @param $userId
	 * @param null $vehicleType
	 * @return mixed
	 *
	 */
	private function getUserRequest($userId, $vehicleType = null, $status = 1)
	{
		$requestInfo = new Requests();

		$userRequest = $requestInfo->join('users', 'requests.user_id', '=', 'users.id')
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

			);
		if($vehicleType !== NULL) {
			$userList = $userRequest->where('requests.user_id', '!=', $userId);
			if($vehicleType == 0) {
				$result = $userList->where('requests.vehicle_type', '!=', '0')->get();
			} else {
				$result = $userList->where('requests.vehicle_type', '=', '0')->get();
			}
		} else {
			$result = $userRequest->where('requests.user_id', '=', $userId)->where('status', '=', $status)->first();
		}
		return json_decode($result);
	}
}