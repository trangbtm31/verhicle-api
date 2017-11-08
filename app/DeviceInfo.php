<?php
/**
 * Created by PhpStorm.
 * User: trang
 * Date: 10/22/17
 * Time: 8:42 PM
 */

namespace App;

use Illuminate\Database\Eloquent\Model;


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

}