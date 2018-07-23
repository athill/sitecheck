<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    //
    public function statuses() {
    	return $this->hasMany('App\Status');
    }

    public function check() {
    	$this->belongsTo('App\Check');
    }    


    public static function latest(string $url) {
    	return $latest = Site::where('url', $url)->with('statuses')->orderBy('created_at', 'desc')->first();
    }
}
