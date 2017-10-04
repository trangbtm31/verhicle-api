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

	public function getRequestFromNeeders(Request $request) {
		 RequestFromNeeders::create([
			'user_id' => $this->userId,
			'source_location' => $request->get('source_location'),
			'destination_location' => $request->get('destination_location'),
			'time_start' => $request->get('time_start'),
			'date_start' => $request->get('date_start'),
			'device_id' => $request->get('device_id')
		]);

		return $this->success([
			"message"=> "Success"
		], 201);
	}
}