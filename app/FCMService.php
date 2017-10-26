<?php
/**
 * Created by PhpStorm.
 * User: trang
 * Date: 10/22/17
 * Time: 8:42 PM
 */

namespace App;

use Illuminate\Database\Eloquent\Model;


class FCMService extends Model
{
	protected $fillable = ['id', 'user_id', 'token'];

	/**
	 * Define a BelongsTo relationship with App\User
	 */
	public function users()
	{
		return $this->belongsTo('App\User');
	}

}