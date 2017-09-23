<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
	public function success($data, $code)
	{
		return response()->json(['status' =>['error' => 0, 'message' => 'Success'], 'data' => $data], $code);
	}

	public function error($errorCode, $message, $code)
	{
		return response()->json(['status' =>['error' => $errorCode, 'message' => $message ]], $code);
	}
	//
}
