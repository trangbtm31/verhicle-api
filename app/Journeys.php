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
    protected $fillable = ['id', 'request_id_needer', 'user_id_needer', 'request_id_graber', 'user_id_graber', 'status', 'sender_id'];

    protected $hidden = ['created_at', 'updated_at'];

}