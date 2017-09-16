<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
	public function success($data, $code)
	{
		return response()->json(['error' => 0, 'data' => $data], $code);
	}

	public function error($message, $code)
	{
		return response()->json(['error' => 1, 'data' => $message], $code);
	}
	//
}
