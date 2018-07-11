<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Service\SiteCheckService;

class CheckSite extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:site {site}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retrieve data about site health';

    protected $service;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->service = new SiteCheckService(' ', $this->signature[0]);
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $url = $this->argument('site');
        $response = $this->service->checkSite($url);
        if (array_key_exists(SiteCheckService::httpStatusKey, $response)) {
            $this->error('Invalid status for site [' . $url . ']: ' . $response[SiteCheckService::httpStatusKey]);
            return;
        }
        $data = file_get_contents($url);
        $this->info(json_encode($data));
    }
}
