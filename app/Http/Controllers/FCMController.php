<?php
/**
 * Created by PhpStorm.
 * User: trang
 * Date: 10/22/17
 * Time: 12:47 AM
 */

namespace App\Http\Controllers;

use LaravelFCM\Message\OptionsBuilder;
use LaravelFCM\Message\PayloadDataBuilder;
use LaravelFCM\Message\PayloadNotificationBuilder;
use FCM;
use App\FCMService;
use Illuminate\Http\Request;


class FCMController extends Controller
{
	public function sendRequest(Request $request) {
		$optionBuilder = new OptionsBuilder();
		$optionBuilder->setTimeToLive(60*20);

		$notificationBuilder = new PayloadNotificationBuilder('my title');
		$notificationBuilder->setBody('Hello world')
			->setSound('default');

		$dataBuilder = new PayloadDataBuilder();
		$dataBuilder->addData(['a_data' => 'my_data']);

		$option = $optionBuilder->build();
		$notification = $notificationBuilder->build();
		$data = $dataBuilder->build();

		$token = FCMService::select('token')->where('user_id','=',$request->get('end_user_id'));

		$downstreamResponse = FCM::sendTo($token, $option, $notification, $data);

		$downstreamResponse->numberSuccess();
		$downstreamResponse->numberFailure();
		$downstreamResponse->numberModification();

//return Array - you must remove all this tokens in your database
		$downstreamResponse->tokensToDelete();

//return Array (key : oldToken, value : new token - you must change the token in your database )
		$downstreamResponse->tokensToModify();

//return Array - you should try to resend the message to the tokens in the array
		$downstreamResponse->tokensToRetry();

// return Array (key:token, value:errror) - in production you should remove from your database the tokens

	}

}