<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Post extends Model{
    protected $fillable = ['id', 'user_id', 'title', 'content'];
    protected $hidden   = ['created_at', 'updated_at'];
}
