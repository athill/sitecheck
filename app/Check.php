<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

// use App\

class Check extends Model
{
     public function sites() {
    	return $this->hasMany('App\Site');
    }
}
