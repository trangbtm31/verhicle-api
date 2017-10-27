<?php
/**
 * Created by PhpStorm.
 * User: trang
 * Date: 9/16/17
 * Time: 3:50 PM
 */

namespace App\Http\Controllers;

use App\User;
use App\Requests;
use App\FCMService;
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
	public function getRequest(Request $request)
	{
        $timeStart = date("h:i", strtotime( $request->get('time_start')));

		$vehicleType = $request->get('vehicle_type');
		$userId = $this->userId;
		//$fcmToken = $request->get('fcm_token');
		$result = array();
		$isExistRequest = Requests::where('user_id', '=', $userId)->where('delete_at', '!=', NULL)->first();
		if(!empty($isExistRequest)) {
		    return $this->error(
                1, "Transaction not yet completed", 200
            );
        }
		Requests::create(
			[
				'user_id' => $userId,
				'source_location' => $request->get('source_location'),
				'destination_location' => $request->get('destination_location'),
				'time_start' => $timeStart,
				'vehicle_type' => $vehicleType,
				'device_id' => $request->get('device_id'),
			]
		);
		/*$isExistToken = FCMService::where('token', '=', $fcmToken)->first();
		if (!$isExistToken) {
			FCMService::create(
				[
					'user_id' => $userId,
					'token' => $fcmToken,
				]
			);
		}*/
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

	/**
	 * @param $vehicleType
	 */
	private function getActiveUser($vehicleType, $userId)
	{
		if ($vehicleType == 0) {
			$activeUser = Requests::join('users', 'requests.user_id', '=', 'users.id')
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
				->where('requests.vehicle_type', '!=', '0')->where('users.id', '!=', $userId)->get();
		} else {
			$activeUser = Requests::join('users', 'requests.user_id', '=', 'users.id')
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
				->where('requests.vehicle_type', '=', '0')->where('users.id', '!=', $userId)->get();
		}

		return json_decode($activeUser);
	}
}