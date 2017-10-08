<?php
/**
 * Created by PhpStorm.
 * User: trang
 * Date: 9/16/17
 * Time: 3:50 PM
 */

namespace App\Http\Controllers;

use App\User;
use App\RequestFromNeeders;
use Illuminate\Http\Request;


class RequestController extends Controller
{
	protected $userId;

	public function __construct(Request $request)
	{
		$this->userId = $request->get('user_id');
	}

	public function getRequest(Request $request)
	{
		RequestFromNeeders::create(
			[
				'user_id' => $this->userId,
				'source_location' => $request->get('source_location'),
				'destination_location' => $request->get('destination_location'),
				'time_start' => $request->get('time_start'),
				'date_start' => $request->get('date_start'),
				'vehicle_type' => $request->get('vehicle_type'),
				'device_id' => $request->get('device_id'),
			]
		);
		if ($request->get('vehicle_type') == 0) {
			$hasVehicle = RequestFromNeeders::join('users', 'request_from_needers.user_id', '=', 'users.id')
				->select(
					'users.id',
					'users.phone',
					'users.email',
					'users.name',
					'users.address',
					'users.gender',
					'users.birthday',
					'users.avatar_link',
					'request_from_needers.vehicle_type'
				)
				->where('request_from_needers.vehicle_type', '!=', '0')->get();

			return $this->success(
				[
					"user_vehicle_list" => $hasVehicle,
				],
				200
			);
		} else {
			return $this->success('', 200);
		}

	}
}