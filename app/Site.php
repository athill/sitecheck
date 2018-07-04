<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    //
    public function statuses() {
    	return $this->hasMany('App\Status');
    }
}
