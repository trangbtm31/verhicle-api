<?php

namespace App\Http\Controllers;

use App\DeviceInfo;
use App\Favorites;
use App\Journeys;
use App\Rating;
use App\User;
use App\Requests;

use Illuminate\Http\Request;
use Carbon\Carbon;

class UserController extends Controller
{

	public function __construct()
	{
		//
	}

	public function index()
	{

		$users = User::all();

		return $this->success(200);
	}

	/**
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function register(Request $request)
	{
		$user = new User();
		$this->validateRequest($request);
		$reqPhoneNumber = $request->get('phone');
		$existPhoneNumber = $user->where('phone', $reqPhoneNumber)->first();

		if ($existPhoneNumber) {
			return $this->error(1, "This phone number is registered with another account", 200);
		}

		$createUser = $user->create(
			[
				'phone' => $reqPhoneNumber,
				'name' => $request->get('name'),
				'gender' => $request->get('gender'),
				'password' => $request->get('password'),
				'google_id' => $request->get('google_id'),
				'facebook_id' => $request->get('facebook_id'),
			]
		);

		/*        return $this->success("The user with with id {$user->id} has been created", 201);*/

		if ($createUser) {
			return $this->success(200);
		} else {
			return $this->error(1, "Something went wrong", 200);
		}
	}

	/**
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function signin(Request $request)
	{
		$userId = User::verify($request->get('phone'), $request->get('password'));
		if ($userId) {
			$userInfo = new User();
			$deviceInfo = new DeviceInfo();
			$cancelRequest = Requests::cancelRequest($userId);
			$deviceId = $request->get('device_id');

			$isActiveUser = $userInfo->where('phone', $request->get('phone'))->first();
			$isExistDeviceId = $deviceInfo->where('user_id', '=', $userId)->first();
			if (!$isExistDeviceId) {
				$deviceInfo->create(
					[
						'user_id' => $userId,
						'device_id' => $deviceId,
					]
				);
			} elseif (empty($isExistDeviceId->device_id)) {
				$isExistDeviceId->device_id = $deviceId;
				$isExistDeviceId->save();
			}
			if (!empty($isActiveUser->api_token)) {
				return $this->success(
					200,
					"data",
					[
						"api_token" => $isActiveUser->api_token,
						"user_info" => [
							"id" => $isActiveUser->id,
							"name" => $isActiveUser->name,
							"phone" => $isActiveUser->phone,
							"email" => $isActiveUser->email,
							"avatar_link" => $isActiveUser->avatar_link,
							"gender" => $isActiveUser->gender,
							"address" => $isActiveUser->address,
							"birthday" => $isActiveUser->birthday,
						],

					]
				);
			}
			$api_token = str_random(30).Carbon::now()->timestamp;
			$isActiveUser->api_token = $api_token;
			$isActiveUser->save();

			return $this->success(
				200,
				"data",
				[
					"api_token" => $api_token,
					"user_info" => [
						"id" => $isActiveUser->id,
						"name" => $isActiveUser->name,
						"phone" => $isActiveUser->phone,
						"email" => $isActiveUser->email,
						"avatar_link" => $isActiveUser->avatar_link,
						"gender" => $isActiveUser->gender,
						"address" => $isActiveUser->address,
						"birthday" => $isActiveUser->birthday,
					],

				]
			);
		} else {
			return $this->error(1, "Invalid phone number or password", 200);
		}

	}

	/**
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function signOut(Request $request)
	{
		$user = $request->user();

		if (!$user) {
			return $this->error(0, "You haven't logged in", 200);
		}
		$cancelRequest = Requests::cancelRequest($user->id);

		$user->api_token = null;

		$user->save();

		return $this->success(200);
	}

	/**
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function show(Request $request)
	{
		$favorite = new Favorites();
		$userId = $request->get('user_id');
		$result = array();
		if (isset($userId)) {
			$user = new User();
			$user = $user->find($request->get('user_id'));
			$isFavorite = $favorite->where('user_id', '=', $request->user()->id)->where(
				'favorited_user_id',
				'=',
				$user->id
			)->first();
			$result = [
				"is_favorite" => !empty($isFavorite) ? 1 : 0,
				"user_info" => $user
			];
		} else {
			$user = $request->user();
			$result = [
				"user_info" => $user
			];
		}
		if (!$user) {
			return $this->error(1, "This user with doesn't exist", 200);
		}
		return $this->success(200, 'information', $result);
	}

	/**
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function update(Request $request)
	{
		$user = $request->user();

		if (!$user) {
			return $this->error(0, "This user with doesn't exist", 200);
		}
		if (!empty($user->api_token)) {
			$userFields = array(
				'name',
				'email',
				'avatar_link',
				'password',
				'gender',
				'address',
				'birthday',
			);
			foreach ($userFields as $userField) {
				if (null !== ($request->get($userField))) {
					if ($userField == 'birthday') {
						$user->birthday = date('Y-m-d', strtotime($request->get('birthday')));
					} else {
						$user->$userField = $request->get($userField);
					}
					$user->save();
				}
			}

			return $this->success(200, 'user_info', $user);
		} else {
			return $this->error(1, "This user with haven't logged in", 200);

		}
	}

	public function getUserHistory(Request $request)
	{
		$user = $request->user();
		$userId = $request->get('user_id') ? $request->get('user_id') : $user->id;

		if($request->get('user_type') == 'driver') {
			$successHistory = $this->getUserTrip($userId, 3,true);
			$failHistory = $this->getUserTrip($userId, 0,true);
		} elseif ($request->get('user_type') == 'hiker') {
			$successHistory = $this->getUserTrip($userId, 3,false);
			$failHistory = $this->getUserTrip($userId, 0,false);
		} else {
			return $this->error(1, 'Your type request is wrong', 200);
		}
		$result = array(
		    [
				"success" => $successHistory, // When user is a driver
				"fail" => $failHistory, // When user is a hiker
				"user_type" => $request->get('user_type')
            ]
        );
        return $this->success(200, 'user_history_info', $result);

	}

    /**
     * @param $userId
     * @param $status
     * @param bool $isDriver
     * @return array
     */
	private function getUserTrip($userId, $status, $isDriver = true) {
		$journey = new Journeys();
		$requests = new Requests();
        $rating = new Rating();
		$users = new User();

		$result = array();
		if($isDriver) {
			$userJourneyList = $journey->where('driver_id', '=', $userId)
					->where('status', '=', $status)
					->get();
			$partner = 'hiker_id';

		} else {
			$userJourneyList= $journey->where('hiker_id', '=', $userId)
				->where('status', '=', $status)
				->get();
            $partner = 'driver_id';
		}
		if($userJourneyList) {
            foreach($userJourneyList as $userJourney) {
                $requestInfo = $requests->find($userJourney->request_driver_id);
                $ratingInfo = $rating->where('user_id', '=', $userId)->where('journey_id', '=', $userJourney->id)->orderBy('id', 'desc')->take(1)->first();
                if(!$ratingInfo) {
                    $userRating = 0;
					$userComment = '';
                } else {
                    $userRating = $ratingInfo->rating_value;
                    $userComment = $ratingInfo->comment;
                }
                $partnerInfo = $users->find($userJourney->$partner);
                $ratingPartnerInfo = $rating->where('user_id', '=', $userJourney->$partner)->where('journey_id', '=', $userJourney->id)->first();
				if(!empty($ratingPartnerInfo)){
					$result[] = [
						'journey' => [
							'id' => $userJourney->id,
							'rating_value' => $userJourney->rating_value,
							'start_time' => $userJourney->created_at,
							'finish_time' => $userJourney->finish_at,
							'cancel_time' => $userJourney->delete_at,
							'start_location' => json_decode($requestInfo->source_location),
							'end_location' => json_decode($requestInfo->destination_location),
							'partner' => $partnerInfo,
							'partner_rating' => $ratingPartnerInfo
						],
						'user_action' =>[
							'rating_value' => $userRating,
							'comment' => $userComment
						]
					];
				} else {
					$result[] = [
						'journey' => [
							'id' => $userJourney->id,
							'rating_value' => $userJourney->rating_value,
							'start_time' => $userJourney->created_at,
							'finish_time' => $userJourney->finish_at,
							'cancel_time' => $userJourney->delete_at,
							'start_location' => json_decode($requestInfo->source_location),
							'end_location' => json_decode($requestInfo->destination_location),
							'partner' => $partnerInfo
						],
						'user_action' =>[
							'rating_value' => $userRating,
							'comment' => $userComment
						]
					];
				}
            }
        }
		return $result;
	}

	/**
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function addToFavorites(Request $request) {
		$favorite = new Favorites();
		$user = $request->user();
		$userId = $user->id;
		$partnerId = $request->get('partner_id');

		if(!empty($favorite->where('user_id', '=', $userId)->where('favorited_user_id', '=', $partnerId)->first())) {
			return $this->error(1, 'You have added this user to your favorite list', 200);
		}

		$favorite->create([
			'user_id' => $userId,
			'favorited_user_id' => $partnerId
		]);

		return $this->success(200);
	}

	/**
	 * @param Request $request
	 */
	public function validateRequest(Request $request)
	{

		$rules = [
			'phone' => 'required|digits_between:9,11',
			'password' => 'required|min:6',
			'name' => 'required',
			'gender' => 'required|digits_between:0,1',
		];

		$this->validate($request, $rules);
	}

}