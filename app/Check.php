<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

// use App\

class Check extends Model
{
     public function sites() {
    	return $this->hasMany('App\Site');
    }

    public static function summary($start, $end) {
        $checks = Check::query();
 
        $checks->whereBetween('created_at', [$start, $end]);

        return $checks->with('sites', 'sites.statuses')->get();    	
    }
}
