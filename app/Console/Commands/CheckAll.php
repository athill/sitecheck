<?php

namespace App\Console\Commands;

use Log;
use Illuminate\Console\Command;

use App\Site;
use App\Status;
use App\Service\SiteCheckService;

class CheckAll extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check all configured sites and update db';


    private $service;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->service = new SiteCheckService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $messages = []; //// messages for publication
        $configPath = storage_path() . '/data/config.json';
        $config = json_decode(file_get_contents($configPath), true); 
        $sites = [];
        foreach ($config as $url) {
            $response = $this->service->checkSite($url);
            $messages[$url] = [];
            $statuses = [];
            $latest = Site::where('url', $url)->orderBy('created_at', 'desc')->first();
            $latest_statuses = $latest->statuses->toArray();

            if (array_key_exists(SiteCheckService::httpStatusKey, $response)) {
                $this->error('Invalid status for site [' . $url . ']: ' . $response[SiteCheckService::httpStatusKey]);
                $status = [
                    'key' => 'web',
                    'up' => false,
                    'message' => 'Invalid Status: ' . $response[SiteCheckService::httpStatusKey]
                ];                
                $statuses[] = $status;
                $latest_status = $this->getStatus($latest_statuses, 'web');
                if ($latest_status && $status['up'] != $latest_status['up']) {
                    $messages[$url][] = $status['message'];    
                }
                
            } else {
                $this->info('Response for url ['. $url . ']:' . json_encode($response));
                foreach ($response as $key => $value) {
                    $status = [
                        'key' => $key,
                        'up' => $value,
                    ];
                    $statuses[] = $status;
                }
            }
            // save or not
            $new = is_null($latest);
            // new site
            if ($new) {
                $messages[$url][] = "New site [$url] with keys: " . json_encode(array_pluck($statuses, 'key'));
                foreach ($statuses as $status) {
                    if (!$status['up']) {
                        $messages[$url][] = $status['key'] . ' is down!';
                    }
                }
            // existing site, check for differences
            } else {
                
                $latest_keys = array_pluck($latest_statuses, 'key');
                $keys = array_pluck($statuses, 'key');
                $set = array_unique(array_merge($keys, $latest_keys));

                
                // print_r($keys);
                foreach ($set as $key) {
                    // new key
                    if (!in_array($key, $latest_keys)) {
                        $messages[$url][] = '[' . $key . '] has been added';
                        continue;
                    // removed key
                    } else if (!in_array($key, $keys)) {
                        $messages[$url][] = '[' . $key .  '] has been removed';
                        continue;
                    // change in status
                    } else {
                        $status = $this->getStatus($statuses, $key);
                        // print_r($status);
                        $latest_status = $this->getStatus($latest_statuses, $key);
                        // $latest_status = array_first($latest_statuses, function($value) use ($key)  {
                        //     return $value['key'] === $key;
                        // });    
                        // print_r($latest_status);
                        if ($status['up'] != $latest_status['up']) {
                            $good = $status['up'];
                            $previous = $good ? 'down' : 'up';
                            $current = $good ? 'up' : 'down';
                            $prelude = $good ? 'Yay!' : 'Danger:';
                            $messages[$url][] =  "$prelude [$key] is $current, was $previous";                            
                        }
                    }
                }
            }
            print_r($messages);
            if (count($messages[$url])) {
                $this->info('saving ' .$url);
                $site = new Site;
                $site->url = $url;
                $site->save();
                $site_id = $site->id;
                $statuses = array_map(function($status) use ($site_id) { $status['site_id'] = $site_id; return $status; }, $statuses);
                Status::insert($statuses);
            } else {
                $this->info('not saving' . $url);
            }
        }
    }

    private function getStatus(array $statuses, string $key) {
        return array_first($statuses, function($value) use ($key)  {
            return $value['key'] === $key;
        });        
    }
}
