<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

use App\Site;
use App\Check;
use App\Status;

class SitecheckService {

	const httpStatusKey = 'http_status';
    protected $command;

    public function __construct($command='') {
        $this->command = $command;
    }

    public function getConfig() {
        $configPath = storage_path() . '/data/config.json';
        return json_decode(file_get_contents($configPath), true); 
    }

    public function process(array $urls, bool $save = false, bool $publish = false) {
        $sites = [];
        foreach ($urls as $url) {
            $latest = Site::where('url', $url)->orderBy('created_at', 'desc')->first();
            $messages = [];
            $latest_statuses = (is_null($latest)) ? [] :  $latest->statuses()->get()->toArray();            
            $statuses = $this->getStatuses($url, $latest_statuses);
            // save or not
            $new = is_null($latest);
            // new site
            if ($new) {
                $messages[] = "New site [$url] with keys: " . json_encode(array_pluck($statuses, 'key'));
                foreach ($statuses as $status) {
                    if (!isset($status['up'])) {
                        $messages[] = $status['key'] . ' is down!';
                    }
                }
            // existing site, check for differences
            } else {
                
                $latest_keys = array_pluck($latest_statuses, 'key');
                $current_keys = array_pluck($statuses, 'key');
                $set = array_unique(array_merge($current_keys, $latest_keys));
                
                // print_r($current_keys);
                foreach ($set as $key) {
                    // new key
                    if (!in_array($key, $latest_keys)) {
                        $messages[] = '[' . $key . '] has been added';
                        continue;
                    // removed key
                    } else if (!in_array($key, $current_keys)) {
                        $messages[] = '[' . $key .  '] has been removed';
                        continue;
                    } else if (isset($status['message'])) {
                        $messages[] = $status['message'];                   
                        continue;
                    // change in status
                    } else {
                        $status = $this->getStatusByKey($statuses, $key);
                        $latest_status = $this->getStatusByKey($latest_statuses, $key);
                        if ($status['up'] != $latest_status['up']) {
                            $good = $status['up'];
                            $previous = $good ? 'down' : 'up';
                            $current = $good ? 'up' : 'down';
                            $prelude = $good ? 'Yay!' : 'Danger:';
                            $messages[] =  "$prelude [$key] is $current, was $previous";     
                        }
                    }
                }
            }
            $sites[$url] =  [
                'messages' => $messages,
                'statuses' => $statuses
            ];
        }
        if ($save) {
            $this->save($sites);
        }
        if ($publish) {
            Mail::to(config('mail.to'))->send(new \App\Mail\Notify($sites));
            
        }
        return $sites;
    } 

    public function range(Carbon $start=null, Carbon $end=null) {
        $checks = Check::query();
        if (is_null($start)) {
            $start = Carbon::now()->subWeek(3);
        }
        if (is_null($end)) {
            $end = Carbon::now();
        }
        var_dump($start);
        $checks->whereBetween('created_at', [$start, $end]);

        $result = $checks->with('sites', 'sites.statuses')->get();
        $response = [];
        foreach ($result as $check) {
            // var_dump($check->sites()->get());
            foreach ($check->sites()->get() as $site) {
                if (!isset($response[$site->url])) {
                    $response[$site->url] = [
                        'keys' => [],
                        'data' =>[]
                    ];
                }
                foreach ($site->statuses()->get() as $status) {
                    if (!in_array($status->key, $response[$site->url]['keys'])) {
                        $response[$site->url]['keys'][] = $status->key;
                    }
                }
            }
        }
        // var_dump($result);
        return $response;
    }

    protected function getStatuses(string $url, array $latest_statuses) {
        $response = $this->checkSite($url);
        
        $statuses = [];

        if (array_key_exists(self::httpStatusKey, $response)) {
            $up = $response[self::httpStatusKey] === 200;
            
            $status = [
                'key' => 'web',
                'up' => $up
            ];
            if (!$up) {
                $status['message'] = 'Invalid Status: ' . $response[self::httpStatusKey];
            } else {
                $status['message'] = 'Not a key-value response, but site appears to be up';
            }
            $statuses[] = $status;
            $latest_status = $this->getStatusByKey($latest_statuses, 'web');

            
        } else {
            foreach ($response as $key => $value) {
                $status = [
                    'key' => $key,
                    'up' => $value,
                ];
                $statuses[] = $status;
            }
        }
        return $statuses;        
    }

    protected function save(array $sites) {
        $check = new Check;
        $check->command = $this->command;
        $check->save();
        foreach ($sites as $url => $data) {
            $statuses = $data['statuses'];
            $site = new Site;
            $site->url = $url;
            $site->check_id = $check->id;
            $site->save();
            $site_id = $site->id;
            $statuses_to_save = array_map(function($status) use ($site_id) { 
                    $now = \Carbon\Carbon::now()->toDateTimeString();
                    $status = array_merge($status, [
                        'site_id' => $site_id,
                        'created_at' => $now,
                        'updated_at' => $now
                    ]);
                    return $status; 
                }, 
                $statuses
            );
            Status::insert($statuses_to_save);   
        }
     
    }            

	public function checkSite($url) {
	    $status = $this->getHttpStatus($url);
        if ($status !== 200) {
            return [ self::httpStatusKey => $status ];
        }

        $data = file_get_contents($url);
        $result = json_decode($data, true);

        if (is_null($result)) {
            return [ self::httpStatusKey => 200 ];   
        }
        return $result;
    }

    public function getHttpStatus($url) {
        $ch = curl_init($url);    
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
       return $code;
    } 

    private function getStatusByKey(array $statuses, string $key) {
        return array_first($statuses, function($value) use ($key)  {
            return $value['key'] === $key;
        });        
    }

}