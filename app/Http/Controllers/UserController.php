<?php

namespace App\Http\Controllers;

use App\DeviceInfo;
use App\User;
use App\Requests;

use Illuminate\Http\Request;

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
            } elseif(empty($isExistDeviceId->device_id)) {
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
			$api_token = str_random(30);
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

		$user->api_token = '';

		$user->save();

		return $this->success(200);
	}

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
	public function show(Request $request)
	{
	    $userId = $request->get('user_id');
	    if(isset($userId)) {
	        $user = new User();
	        $user = $user->find($request->get('user_id'));
        } else {
            $user = $request->user();
        }
		if (!$user) {
			return $this->error(1, "This user with doesn't exist", 200);
		}

		return $this->success(200, 'user_info', $user);
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
					if($userField == 'birthday') {
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

	public function destroy($id)
	{

		$user = User::find($id);

		if (!$user) {
			return $this->error("The user with {$id} doesn't exist", 200);
		}

		$user->delete();

		return $this->success("The user with with id {$id} has been deleted", 200);
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