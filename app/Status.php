<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    //

    public function site() {
    	$this->belongsTo('App\Site');
    }
}
