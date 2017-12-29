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

use Faker\Provider\DateTime;
use Carbon\Carbon;
use Illuminate\Http\Request;


class JourneyController extends Controller
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
        $srcLocation = $request->get('source_location');
        $desLocation = $request->get('destination_location');
        $lat1 = json_decode($srcLocation)->lat;
        $lng1 = json_decode($srcLocation)->lng;
        $lat2 = json_decode($desLocation)->lat;
        $lng2 = json_decode($desLocation)->lng;
        $startTime = new DateTime(strtotime($request->get('time_start')));
        $currentTime = Carbon::now();
        $userDistance = $this->getDistance($lat1, $lng1, $lat2, $lng2, 'M');

        $interval = date_diff(new DateTime($startTime),new DateTime(date("h:i", strtotime('5:30:01'))));
        echo $interval->format('%R%a days');die;

        $activeRequests = $requestInfo->where('user_id', '=', $userId)->where('status', '=', 1)->get();
        foreach ($activeRequests as $activeRequest) {
            $activeRequest->status = 0;
            $activeRequest->save();
        }
        $requestInfo->create(
            [
                'user_id' => $userId,
                'source_location' => $srcLocation,
                'destination_location' => $request->get('destination_location'),
                'time_start' => $startTime,
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
        $activeUsers = $this->getUserRequest($userId, $vehicleType, 1, $currentTime);

        foreach ($activeUsers as $activeUser) {
            $srcLocation = json_decode($activeUser->source_location);
            $desLocation = json_decode($activeUser->destination_location);
            $activeLat1 = $srcLocation->lat;
            $activeLng1 = $srcLocation->lng;
            $activeLat2 = $desLocation->lat;
            $activeLng2 = $desLocation->lng;
            $userActiveDistance = $this->getDistance($activeLat1, $activeLng1, $activeLat2, $activeLng2, 'M');
            $startDistance = $this->getDistance($lat1, $lng1, $activeLat1, $activeLng1, 'M');
            $destinationDistance = $this->getDistance($lat2, $lng2, $activeLat2, $activeLng2, 'M');
            if ($startDistance <= 500 && $destinationDistance <= 500) {
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
                            "source_location" => $srcLocation,
                            "dest_location" => $desLocation,
                            "time_start" => $activeUser->time_start,
                        ],
                    ]
                );
            }
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
        $deviceInfo = new DeviceInfo();
        $journey = new Journeys();
        $user = $this->user;
        $userId = $user->id;
        $receiverId = $request->get('receiver_id');

        $userRequest = $this->getUserRequest($userId);
        if (!$userRequest) {
            return $this->error(
                1,
                "You haven't post any request yet !",
                200
            );
        }

        $data = [
            'data' => [
                'type' => 'send_request',
                'user_id' => $userId,
                'user_name' => $userRequest->name,
                'start_location' => json_decode($userRequest->source_location),
                'end_location' => json_decode($userRequest->destination_location),
                'avatar_link' => $userRequest->avatar_link,
                'start_time' => $userRequest->time_start,
                'vehicle_type' => $userRequest->vehicle_type,
                'note' => $request->get('note'),
            ]
        ];

        $result = $deviceInfo->pushNotification($receiverId, $data);

        $requestInfo = $journey->find($userRequest->id);
        $receiverRequestInfo = $journey->where('user_id', '=', $receiverId)->where('status', '=', 1)->orderBy('id', 'desc')->first();
        if ($result['success']) {
            $requestInfo->status = 2; // This request owner has sent request to another user. ( status change to pending )
            $requestInfo->save();
            $receiverRequestInfo->status = 2; // This request owner has received request to another user. ( status change to pending )
            $receiverRequestInfo->save();
        }

        return $this->success(
            200,
            "send_request_info",
            $result
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
        $userId = $user->id;
        $journey = new Journeys();
        $deviceInfo = new DeviceInfo();

        // Get info of pending journey.
        if($request->get('vehicle_type') == 0 ) {
            $journeyInfo = $journey
                ->where('user_id_needer', '=', $userId)
                ->where('status', '=', '1')->first();
        } else {
            $journeyInfo = $journey
                ->where('user_id_grabber', '=', $userId)
                ->where('status', '=', '1')->first();
        }

        if(!$journeyInfo) {
            return $this->error(
                1,
                'This journey is not active',
                200
            );
        }

        $journeyInfo->status = 2; // Change status to started.

        $data = [
            'data' => [
                'type' => 'start_the_trip',
                'journey_id' => $journeyInfo->id,
                "start_time" => date('Y-m-d H:i:s', time())
            ]
        ];
        $notifyInfo = $deviceInfo->pushNotification($journeyInfo->user_id_grabber,
            $data);

        $journeyInfo->save();

        $result = array(
            "start_time" => date('Y-m-d H:i:s', time()),
            "detail" => $journeyInfo,
            "notification_info" => $notifyInfo
        );

        return $this->success(
            200,
            "journey_info",
            $result
        );

    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function endTheTrip(Request $request)
    {
        $user = $this->user;
        $userId = $user->id;
        $journeys = new Journeys();
		$deviceInfo = new DeviceInfo();
        $journeyId = $request->get('journey_id');

        $activeJourney = $journeys->where('id', '=', $journeyId)->first();

        if ($activeJourney->status != 2) {
            return $this->error(1, "This journey is not started", 200);
        }
        if( $userId == $activeJourney->user_id_grabber ) {
            $partnerId = $activeJourney->user_id_needer;
        }elseif ( $userId == $activeJourney->user_id_needer) {
            $partnerId = $activeJourney->user_id_grabber;
        }else {
            return $this->error(2, "Permission denied", 200);
        }

        $activeJourney->status = 3; // The journey is finished
        $activeJourney->finish_at = date('Y-m-d H:i:s', time());
		$data = [
            'data' => [
                'type' => 'end_the_trip',
                'journey_id' => $activeJourney->id,
                "start_time" => date('Y-m-d H:i:s', time())
            ]
		];


		$notifyInfo = $deviceInfo->pushNotification($partnerId, $data);

        $activeJourney->save();

		$result = array(
			"end_time" => date('Y-m-d H:i:s', time()),
			"detail" => $activeJourney,
			"notification_info" => $notifyInfo
		);

        return $this->success(
			200,
			'end_journey_info',
			$result
		);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function confirmRequest(Request $request)
    {
        $deviceInfo = new DeviceInfo();
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

            $data = [
                'data' => [
                    'type' => 'confirm_request',
                    'status' => 'accept',
                    'user_id' => $receiverInfo->user_id,
                    'user_name' => $receiverInfo->name,
                    'start_location' => json_decode($receiverInfo->source_location),
                    'end_location' => json_decode($receiverInfo->destination_location),
                    'avatar_link' => $receiverInfo->avatar_link,
                    'start_time' => $receiverInfo->time_start,
                    'vehicle_type' => $receiverInfo->vehicle_type,
                ]
            ];

            $receiverResponseInfo = $deviceInfo->pushNotification( $senderId, $data);

            if ($senderInfo->vehicle_type == 0) {
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
                    "status" => "success",
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
                    "request_info" => $receiverResponseInfo
                ]
            );
        } else {
            $data = [
                'type' => 'confirm_request',
                'status' => 'deny'
            ];
            $receiverResponseInfo = $deviceInfo->pushNotification($senderId, $data);
            $result = array(
                [
                    "status" => "deny",
                    "request_info" => $receiverResponseInfo
                ]
            );
        }

        return $this->success(
            200,
            "confirm_status",
            $result
        );

    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getActiveRequest(Request $request) {
        $user = $this->user;
        $currentTime = Carbon::now();
        $activeRequests = $this->getUserRequest($user->id, $request->get('vehicle_type'), 1, $currentTime);
        if(!$activeRequests) {
            return $this->error(1,"There isn't any request", 200);
        }
        $result = array();
        foreach( $activeRequests as $activeRequest) {
            array_push(
                $result,
                [
                    "user_info" => [
                        "id" => $activeRequest->user_id,
                        "phone" => $activeRequest->phone,
                        "email" => $activeRequest->email,
                        "name" => $activeRequest->name,
                        "address" => $activeRequest->address,
                        "gender" => $activeRequest->gender,
                        "birthday" => $activeRequest->birthday,
                        "avatar_link" => $activeRequest->avatar_link,
                    ],
                    "request_info" => [
                        "vehicle_type" => $activeRequest->vehicle_type,
                        "source_location" => json_decode($activeRequest->source_location),
                        "dest_location" => json_decode($activeRequest->destination_location),
                        "time_start" => $activeRequest->time_start,
                    ],
                ]
            );
        }
        return $this->success(
            200,
            'active_users',
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
    private function getUserRequest($userId, $vehicleType = null, $status = 1, $currentTime = null)
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
                $result = $userList->where('requests.vehicle_type', '!=', '0')->where('requests.status','=',$status)->whereDate('requests.time_start', ">=" , $currentTime )->get();
            } else {
                $result = $userList->where('requests.vehicle_type', '=', '0')->where('requests.status','=',$status)->whereDate('requests.time_start', ">=" , $currentTime)->get();
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

    /**
     * @param $lat1
     * @param $lng1
     * @param $lat2
     * @param $lng2
     * @param $unit
     * @return float
     */
    private function getDistance($lat1, $lng1, $lat2, $lng2, $unit)
    {
        $theta = $lng1 - $lng2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        $unit = strtoupper($unit);

        if ($unit == "M") {
            return ($miles * 1609.344);
        } else {
            if ($unit == "N") {
                return ($miles * 0.8684);
            }
        }
    }
}