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
        $configPath = base_path('sitecheck-config.yml');
        $data = yaml_parse_file($configPath); 
        return $data;
    }

    public function latest() {
        $config = $this->getConfig();
        $result = [];
        foreach ($config['endpoints'] as $url) {
            $result[$url] = Site::latest($url);
        }
        return $result;
    }

    public function notifications(array $urls, bool $save = false, bool $publish = false) {
        $sites = [];
        foreach ($urls as $url) {
            $latest = Site::latest($url);
            $messages = [];
            $latest_statuses = (is_null($latest)) ? [] :  $latest->statuses->toArray();            
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

    public function summary($options=[]) {
        $defaultOptions = [
            'start' => Carbon::now()->subWeek(5),
            'end' => Carbon::now(),
            'publish' => false
        ];
        $options = array_merge($options, $defaultOptions);
        $result = Check::summary($options['start'], $options['end']);
        $statuses = [];
        $response = [];
        $messages = [];
        $first = null;
        $last = null;
        foreach ($result as $i => $check) {
            if ($i === 0) {
                $first = $check->created_at;    
            }
            $last = $check->created_at;
            foreach ($check->sites as $site) {
                $url = $site->url;
                if (!isset($statuses[$url])) {
                    $statuses[$url] = [];                    
                }
                if (!isset($messages[$url])) {
                    $messages[$url] = [];                    
                }                
                //// find keys that have been removed
                $current_keys = array_pluck($site->statuses->toArray(), 'key');
                if ($i > 0) {
                    foreach ($statuses[$url] as $key => $value) {
                        if (!in_array($key, $current_keys)) {
                            $messages[$url][] = 'Key ' . $key . ' removed as of ' . $check->created_at->toDateTimeString();
                            unset($statuses[$url][$key]);
                        }
                    }
                }
                foreach ($site->statuses as $status) {
                    //// add initial status
                    if ($i === 0) {
                        $statuses[$url][$status->key] = [
                            'up' => $status->up,
                            'date' => $status->created_at
                        ];       
                    //// check status changes
                    } else {
                        //// new key
                        if (!in_array($status->key, array_keys($statuses[$url]))) {

                            $messages[$url][] = 'Key ' . $status->key . ' has been added as of ' . $check->created_at->toDateTimeString() . ' and is ' . ($status->up ? 'up' : 'down');
                        //// change in key
                        } else if ($status->up !== $statuses[$url][$key]['up']) {
                            $key = $status->key;
                            //// status has been down and is now up, add a message
                            if ($status->up) {
                                $statuses_datestring = $statuses[$url][$key]['date']->toDateTimeString();
                                $current_datestring = $status->created_at->toDateTimeString();                                
                                $messages[$url][] = "Key $status->key  was down from $statuses_datestring to $current_datestring"; 
                            }
                            //// regardless update statuses array
                            $statuses[$url][$key] = [
                                'up' => $status->up,
                                'date' => $status->created_at
                            ];

                        }
                    }
                }
            }
        }
        $checks = [
            'start' => $first,
            'end' => $last,
            'messages' => $messages
        ];
        //// TODO: fix this
        if ($options['publish']) {
            Mail::to(config('mail.to'))->send(new \App\Mail\Summary($checks));
            
        }        
        return $checks;
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

    public function save(array $sites) {
        $check = new Check;
        $check->command = $this->command;
        // dd($check);
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