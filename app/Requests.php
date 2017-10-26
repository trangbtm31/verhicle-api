<?php
/**
 * Created by PhpStorm.
 * User: trang
 * Date: 9/16/17
 * Time: 1:21 PM
 */

namespace App;

use Illuminate\Database\Eloquent\Model;


class Requests extends Model
{
	protected $fillable = ['id', 'user_id', 'source_location', 'destination_location', 'time_start', 'vehicle_type'];

	protected $hidden = ['created_at', 'updated_at', 'delete_at'];


	/**
	 * Define a BelongsTo relationship with App\User
	 */
	public function users()
	{
		return $this->belongsTo('App\User');
	}

}