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

	/**
	 * @param $userId
	 * @param null $data
	 * @return array
	 * @throws \LaravelFCM\Message\InvalidOptionException
	 */
	public function pushNotification($userId, $data = null) {
        $optionBuilder = new OptionsBuilder();
        $optionBuilder->setTimeToLive(60*10);

        $dataBuilder = new PayloadDataBuilder();

        // Add payload data
        $dataBuilder->addData($data);

        $option = $optionBuilder->build();
        $dataBuild = $dataBuilder->build();
        $tokenInfo = $this->select('token')->where('user_id', '=', $userId)->first();
        $downstreamResponse = FCM::sendTo($tokenInfo->token, $option, null, $dataBuild);

        // The number of success push notification.
        $isSentSuccess = $downstreamResponse->numberSuccess();

        return [
            "request-data" => $dataBuild->toArray(),
            "is_success" => $isSentSuccess
        ];
    }

	/**
	 * @param $date_1
	 * @param $date_2
	 * @param string $differenceFormat
	 * @return string
	 */
	public function compareTime($date_1 , $date_2 , $differenceFormat = '%i' )
	{
		$datetime1 = date_create($date_1);
		$datetime2 = date_create($date_2);

		$interval = date_diff($datetime1, $datetime2);

		return $interval->format($differenceFormat);

	}

}