<?php

use Illuminate\Database\Seeder;

use App\Site;
use App\Check;
use App\Status;
use App\Services\SitecheckService;

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

    	
    	// $checks = [];
    	$service = new SitecheckService('generated');

    	for ($i = 0; $i < $generate; $i++) {
    		$sites = [];	
    		foreach ($endpoints as $j => $endpoint) {
    			if (!$siteStability) {
    				continue;
    			}

    			$site = [
    				'statuses' => []
    			];

    			foreach ($keys as $k => $key) {
    				$index = $i + $j + $k;
    				$siteUp = $index % $statusStability;
    				$keyUp = $index % $keyStability;
    				$site['statuses'][] = [
    					'key' => $key,
    					'up' => $siteUp && $keyUp
    				];
    			}
    			$sites[$endpoint] = $site;
    		}
    		$service->save($sites);	
    	}
    }
}
