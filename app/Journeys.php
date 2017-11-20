<?php
/**
 * Created by PhpStorm.
 * User: trang
 * Date: 9/16/17
 * Time: 1:21 PM
 */

namespace App;

use Illuminate\Database\Eloquent\Model;


class Journeys extends Model
{
    protected $fillable = ['id', 'request_id_needer', 'user_id_needer', 'request_id_grabber', 'user_id_grabber', 'status', 'sender_id', 'rating_value'];

    protected $hidden = ['user_delete_id', 'finish_at','delete_at', 'created_at', 'updated_at'];

}