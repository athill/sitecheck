<?php

namespace App\Console\Commands;

use DB;
use Log;
use Illuminate\Console\Command;

use App\Check;
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
    protected $signature = 'check:all {--p|publish} {--s|save}';

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
        $this->service = new SiteCheckService(explode(' ', $this->signature[0]));
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $publish = $this->option('publish');
        $save = $this->option('save');
        if (!$publish && !$save) {
            $this->info("Purely reporting, use -p/--publish to notify users and -s/--save to save");
        }
        $messages = []; //// messages for publication
        $configPath = storage_path() . '/data/config.json';
        $config = json_decode(file_get_contents($configPath), true); 
        var_dump($config);
        $sites = $this->service->process($config, $save, $publish);

        foreach ($sites as $url => $data) {
            $this->info('Notices for ' . $url);
            foreach ($data['messages'] as $message) {
                $this->info("\t$message");
            }
        }
        // i         
    }


}
