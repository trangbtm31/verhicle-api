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


class Favorites extends Model
{
	protected $fillable = ['id', 'user_id', 'favorited_user_id'];

	protected $hidden = ['created_at', 'updated_at'];
}