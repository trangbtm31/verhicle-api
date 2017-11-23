<?php
/**
 * Created by PhpStorm.
 * User: trang
 * Date: 10/22/17
 * Time: 8:42 PM
 */

namespace App;

use Illuminate\Database\Eloquent\Model;
use LaravelFCM\Message\OptionsBuilder;
use LaravelFCM\Message\PayloadDataBuilder;
use LaravelFCM\Message\PayloadNotificationBuilder;
use FCM;


class DeviceInfo extends Model
{
	protected $fillable = ['id', 'user_id', 'token'];

	protected $hidden = ['created_at', 'updated_at'];
	/**
	 * Define a BelongsTo relationship with App\User
	 */
	public function users()
	{
		return $this->belongsTo('App\User');
	}

	public function pushNotification($title, $body, $userId, $data = null) {
        $optionBuilder = new OptionsBuilder();
        $optionBuilder->setTimeToLive(60*10);

        $notificationBuilder = new PayloadNotificationBuilder($title);
        $notificationBuilder->setBody($body)
            ->setSound('default');
        $dataBuilder = new PayloadDataBuilder();

        // Add payload data
        $dataBuilder->addData($data);

        $option = $optionBuilder->build();
        $notification = $notificationBuilder->build();
        $dataBuild = $dataBuilder->build();
        $tokenInfo = $this->select('token')->where('user_id', '=', $userId)->first();
        $downstreamResponse = FCM::sendTo($tokenInfo->token, $option, $notification, $dataBuild);

        // The number of success push notification.
        $isSentSuccess = $downstreamResponse->numberSuccess();

        return [
            "request-data" => $dataBuild->toArray(),
            "success" => $isSentSuccess
        ];
    }

}