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
use App\Journeys;
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

		$activeRequests = $requestInfo->where('user_id', '=', $userId)->where('status', '=', 1)->get();
		foreach ($activeRequests as $activeRequest) {
			$activeRequest->status = 0;
			$activeRequest->save();
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
		$isExistUser = $fcmService->where('user_id', '=', $userId)->first();
		if (!$isExistUser) {
			$fcmService->create(
				[
					'user_id' => $userId,
					'token' => $fcmToken,
				]
			);
		} elseif ($isExistUser && $isExistUser->token != $fcmToken) {
			$isExistUser->token = $fcmToken;
			$isExistUser->save();
		}
		$activeUsers = $this->getUserRequest($userId, $vehicleType);
		foreach ($activeUsers as $activeUser) {
			array_push(
				$result,
				[
					"user_info" => [
						"id" => $activeUser->user_id,
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
		if (!$userRequest) {
			return $this->error(
				1,
				"You haven't post any request yet !",
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
	public function cancelRequest()
	{
		$user = $this->user;
		$cancel = Requests::cancelRequest($user->id);
		if ($cancel) {
			return $this->success(200);
		} else {
			return $this->error(1, "You haven't sent any request", 200);
		}
	}

	/**
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function startTheTrip(Request $request)
	{
		$user = $this->user;
		$neederId = $user->id;
		$journey = new Journeys();

		// Get info of pending journey.
		$journeyInfo = $journey
            ->where('user_id_needer', '=', $neederId)
            ->where('status','=','1')->first();

		$journeyInfo->status = 2; // Change status to started.

        $journeyInfo->save();

		$result = array(
			"start_time" => date('Y-m-d H:i:s', time()),
			"detail" => $journeyInfo
		);

		return $this->success(
			200,
			"journey_info",
			$result
		);

	}

	public function confirmRequest(Request $request)
	{
		$user = $this->user;
		$requests = new Requests();
        $journey = new Journeys();
		$senderId = $request->get('sender_id');
		$receiverId = $user->id;
		$confirmId = $request->get('confirm_id'); // if id = 1 is deny, 2 is accept

		$requestSenderInfo = $requests->where('user_id', '=', $senderId)->where('status', '=', 1)->first();
		$requestReceriverInfo = $requests->where('user_id', '=', $receiverId)->where('status', '=', 1)->first();

		if (!$requestSenderInfo or !$requestReceriverInfo) {
			return $this->error(1, "This user haven't sent any request", 200);
		}

		// Change request status to 1 (available) if user delete request
        // Change request status to 2 (pending) if user accept request
		$requestSenderInfo->status = $confirmId;
		$requestReceriverInfo->status = $confirmId;

		$requestSenderInfo->save();
		$requestReceriverInfo->save();

		if ($confirmId == 2) {
			$senderInfo = $this->getUserRequest($senderId, null, 2);
			$receiverInfo = $this->getUserRequest($receiverId, null, 2);

			if($senderInfo->vehicle_type == 0) {
			    $neederId = $senderInfo->user_id;
			    $grabberId = $receiverInfo->user_id;
                $neederRequestId = $senderInfo->id;
                $grabberRequestId = $receiverInfo->id;
            } else {
                $grabberId = $senderInfo->user_id;
                $neederId = $receiverInfo->user_id;
                $neederRequestId = $receiverInfo->id;
                $grabberRequestId = $senderInfo->id;
            }

            $journey->create(
                [
                    'sender_id' => $senderId,
                    'user_id_needer' => $neederId,
                    'request_id_needer' => $neederRequestId,
                    'request_id_grabber' => $grabberRequestId,
                    'user_id_grabber' => $grabberId,
                    'status' => 1, // Is pending for starting the trip
                ]
            );

			$result = array(
				[
					"success" => [
						"sender" => [
							"user_info" => [
								"id" => $senderInfo->user_id,
								"phone" => $senderInfo->phone,
								"email" => $senderInfo->email,
								"name" => $senderInfo->name,
								"address" => $senderInfo->address,
								"gender" => $senderInfo->gender,
								"birthday" => $senderInfo->birthday,
								"avatar_link" => $senderInfo->avatar_link,
							],
							"request_info" => [
								"vehicle_type" => $senderInfo->vehicle_type,
								"source_location" => json_decode($senderInfo->source_location),
								"dest_location" => json_decode($senderInfo->destination_location),
							],
						],
						"receiver" => [
							"user_info" => [
								"id" => $receiverInfo->user_id,
								"phone" => $receiverInfo->phone,
								"email" => $receiverInfo->email,
								"name" => $receiverInfo->name,
								"address" => $receiverInfo->address,
								"gender" => $receiverInfo->gender,
								"birthday" => $receiverInfo->birthday,
								"avatar_link" => $receiverInfo->avatar_link,
							],
							"request_info" => [
								"vehicle_type" => $receiverInfo->vehicle_type,
								"source_location" => json_decode($receiverInfo->source_location),
								"dest_location" => json_decode($receiverInfo->destination_location),
							],
						],
					],
				],
			);
		} else {
			$result = "deny";
		}

		return $this->success(
			200,
			"confirm_status",
			$result
		);

	}

	/**
	 * @param $userId
	 * @return \Illuminate\Database\Eloquent\Model|null|static
	 */
	/*private function getOwnerActiveRequest($userId)
	{
		$requests = new Requests();

		$result = $requests->where('user_id', '=', $userId)->where('status', '=', 1)->first();

		return $result;
	}*/

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
				'users.phone',
				'users.email',
				'users.name',
				'users.address',
				'users.gender',
				'users.birthday',
				'users.avatar_link',
				'requests.id',
				'requests.user_id',
				'requests.vehicle_type',
				'requests.source_location',
				'requests.destination_location',
				'requests.time_start'

			);
		if ($vehicleType != null) {
			$userList = $userRequest->where('requests.user_id', '!=', $userId);
			if ($vehicleType == 0) {
				$result = $userList->where('requests.vehicle_type', '!=', '0')->get();
			} else {
				$result = $userList->where('requests.vehicle_type', '=', '0')->get();
			}
		} else {
			$result = $userRequest->where('requests.user_id', '=', $userId)->where(
				'requests.status',
				'=',
				$status
			)->first();
		}

		return json_decode($result);
	}
}