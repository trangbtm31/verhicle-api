<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
	public function success($code, $typeOfData = null , $data = null)
	{
		if(!empty($typeOfData)) {
			return response()->json(['status' =>['error' => 0, 'message' => 'Success'], $typeOfData => $data], $code);
		} else {
			return response()->json(['status' =>['error' => 0, 'message' => 'Success']], $code);
		}

	}

	public function error($errorCode, $message, $code)
	{
		return response()->json(['status' =>['error' => $errorCode, 'message' => $message ]], $code);
	}
	//
}
