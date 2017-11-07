<?php

namespace App\Http\Controllers;

use App\User;

use Illuminate\Http\Request;

class UserController extends Controller
{

	/*public function __construct()
	{
		$this->middleware('Authorize:' . __CLASS__, ['except' => ['index', 'show']]);
		$this->middleware('oauth', ['except' => ['index', 'show']]);
	}*/

	public function index()
	{

		$users = User::all();

        return $this->success('', '', 200);
	}

	public function register(Request $request)
	{
		$user = new User();
		$this->validateRequest($request);
		$reqPhoneNumber = $request->get('phone');
		$existPhoneNumber = User::where('phone', $reqPhoneNumber)->first();

		if ($existPhoneNumber) {
			return $this->error(1, "This phone number is registered with another account", 200);
		}

		$user->create(
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

		return $this->success('', '', 200);
	}

	public function signin(Request $request)
	{
		$user = User::verify($request->get('phone'), $request->get('password'));
		if ($user) {
			$user = new User();
			$api_token = str_random(30);
			$user->where('phone', $request->get('phone'))
				->update(['api_token' => $api_token]);
			$userInfo = $user->where('phone', $request->get('phone'))->first();

			return $this->success("data",
				[
					"api_token" => $api_token,
					"user_info" => [
						"id" => $userInfo->id,
						"name" => $userInfo->name,
						"phone" => $userInfo->phone,
						"email" => $userInfo->email,
						"avatar_link" => $userInfo->avatar_link,
						"gender" => $userInfo->gender,
						"address" => $userInfo->address,
						"birthday" => $userInfo->birthday,
					],

				],
				200
			);
		} else {
			return $this->error(1, "Invalid phone number or password", 200);
		}

	}

	public function signOut(Request $request) {
		$user = new User();
		$isActiveUser = $user->find($request->user_id);

		if(!$isActiveUser || $isActiveUser->api_token != $request->api_token) {
			return $this->error(0 ,"You haven't logged in", 200);
		}

		$isActiveUser->api_token = '';

		$isActiveUser->save();

		return $this->success(
			'','',200
		);
	}

	public function show($id)
	{

		$user = User::find($id);

		if (!$user) {
			return $this->error("The user with {$id} doesn't exist", 404);
		}

        return $this->success('', '', 200);
	}

	public function update(Request $request, $id)
	{
		$user = new User();
		$isUser = $user->find($id);

		if (!$isUser) {
			return $this->error(0,"The user with {$id} doesn't exist", 200);
		}
		if(!empty($isUser->api_token)) {
			$userFields = array(
				'phone',
				'name',
				'email',
				'avatar_link',
				'password',
				'gender',
				'address',
				'birthday'
			);
			foreach($userFields as $userField) {
				if(null !==($request->get($userField))) {
					$isUser->$userField = $request->get($userField);
					$isUser->save();
				}
			}

			return $this->success(
				'','',200
			);
		} else {
			return $this->error(1,"The user with {$id} haven't logged in", 200);

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

	public function isAuthorized(Request $request)
	{

		$resource = "users";

		// $user     = User::find($this->getArgs($request)["user_id"]);

		return $this->authorizeUser($request, $resource);
	}
}