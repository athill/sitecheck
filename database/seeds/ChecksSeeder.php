<?php

use Carbon\Carbon;
use Illuminate\Database\Seeder;

use App\Site;
use App\Check;
use App\Status;

class ChecksSeeder extends Seeder
{


    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
    	$generate = 20;
    	$siteStability = 15;
    	$statusStability = 30;
    	$keyStability = 17;
        $delay = 10;

    	$endpoints = [
    		'https://wimf.space/api/health',
    		'https://informedelectorate.net/api/health',
    		'https://pizzakingofcarmel.com/api/health',
    		'https://upcbloomington.org/api/health'
    	];

    	$keys = [
    		'web',
    		'db',
    		'redis'
    	];

    	for ($i = 0; $i < $generate; $i++) {
            $check = new Check;
            $datetime = Carbon::now()->subMinutes(($generate - $i) * $delay);
            $check->created_at = $datetime;
            $check->updated_at = $datetime;
            $check->command = 'generated';
    		$check->save();
    		foreach ($endpoints as $j => $endpoint) {
    			if (!$siteStability) {
    				continue;
    			}
                $site = new Site;
                $site->url = $endpoint;
                $site->created_at = $datetime;
                $site->updated_at = $datetime;          
                $site->check_id = $check->id;
                $site->save();

    			foreach ($keys as $k => $key) {
    				$index = $i + $j + $k;
    				$siteUp = $index % $statusStability;
    				$keyUp = $index % $keyStability;
                    $status = new Status;
                    $status->key = $key;
                    $status->up = $siteUp && $keyUp;
                    $status->created_at = $datetime;
                    $status->updated_at = $datetime;  
                    $status->site_id = $site->id;                  
                    $status->save();
    			}
    		}
    	}
    }
}
