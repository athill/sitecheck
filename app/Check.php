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

    public static function summary($start=null, $end=null) {
        $checks = Check::query();
        if (is_null($start)) {
            $start = Carbon::now()->subWeek(3);
        }
        if (is_null($end)) {
            $end = Carbon::now();
        }
        $checks->whereBetween('created_at', [$start, $end]);

        return $checks->with('sites', 'sites.statuses')->get();    	
    }
}
