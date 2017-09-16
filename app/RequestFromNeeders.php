<?php
/**
 * Created by PhpStorm.
 * User: trang
 * Date: 9/16/17
 * Time: 1:21 PM
 */

namespace App;

use Illuminate\Database\Eloquent\Model;


class RequestFromNeeders extends Model
{
	protected $fillable = ['id', 'user_id', 'source_location', 'destination_location', 'start_time', 'start_date'];

	protected $hidden = ['created_at', 'updated_at', 'delete_at'];


	/**
	 * Define a BelongsTo relationship with App\User
	 */
	public function users()
	{
		return $this->belongsTo('App\User');
	}

}