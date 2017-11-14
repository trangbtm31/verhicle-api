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
		$vehicleType = $request->get('vehicle_type');
		$userId = $user->id;
		$fcmToken = $request->get('device_token');
		$deviceId = $request->get('device_id');

		$isExistRequest = $requestInfo->where('user_id', '=', $userId)->where('status', '=', 1)->first();
		if ($isExistRequest) {
			return $this->error(
				1,
				"Transaction is not yet completed",
				200
			);
		}
		$requestInfo->create(
			[
				'user_id' => $userId,
				'source_location' => $request->get('source_location'),
				'destination_location' => $request->get('destination_location'),
				'time_start' => date("h:i", strtotime($request->get('time_start'))),
				'vehicle_type' => $vehicleType,
				'status' => 1,
			]
		);

		// Check if this device info hasn't saved yet
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
		$activeUsers = $this->getUserRequest($userId, $vehicleType);
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
			200,
			"active_users",
			$result
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
		$requests = new Requests();
		$user = $this->user;
		$userId = $user->id;
		$optionBuilder->setTimeToLive(60 * 5);

		$notificationBuilder = new PayloadNotificationBuilder('You have a request!');
		$notificationBuilder->setBody('Hey, would you like to go together?')
			->setSound('default');

		$userRequest = $this->getUserRequest($userId);
		if(!$userRequest) {
			return $this->error(
				1,
				"You sent request to another person !",
				200
			);
		}
		$dataBuilder = new PayloadDataBuilder();

		// Add payload data
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

		// The number of success push notification.
		$isSentSusscess = $downstreamResponse->numberSuccess();
		/*$requestInfo = $requests->where('user_id', '=', $userId)->where('status', '=', 1)->first();
		if ($isSentSusscess) {
			$requestInfo->status = 2; // This request owner has sent request to another user.
			$requestInfo->save();
		}*/

		return $this->success(
			200,
			"FCM_info",
			[
				'data' => $data->toArray(),
				'success' => $isSentSusscess,
			]
		);

	}

	/**
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function cancelRequest() {
		$user = $this->user;
		$cancel = $this->doCancelRequest($user->id);
		if($cancel) {
			return $this->success(200);
		} else {
			return $this->error(1, "You haven't sent any request", 200);
		}
	}

	/**
	 * @param $userId
	 * @param $status
	 * @return int|mixed
	 */
	public function doCancelRequest($userId) {
		$request = new Requests();
		$requestInfo = $request->where('user_id', '=', $userId)->where('status', '!=', 0)->first();
		if($requestInfo) {
			$requestInfo->status = 0;
			$requestInfo->delete_at = date('Y-m-d H:i:s', time());

			$requestInfo->save();
			return $requestInfo->id;
		}

		return 0;
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
		if($vehicleType != NULL) {
			$userList = $userRequest->where('requests.user_id', '!=', $userId);
			if($vehicleType == 0) {
				$result = $userList->where('requests.vehicle_type', '!=', '0')->get();
			} else {
				$result = $userList->where('requests.vehicle_type', '=', '0')->get();
			}
		} else {
			$result = $userRequest->where('requests.user_id', '=', $userId)->where('requests.status', '=', $status)->first();
		}

		return json_decode($result);
	}
}