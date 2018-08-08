<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;

use App\Services\SitecheckService;

class SitecheckSummary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sitecheck:summary 
        {--p|publish : Whether to send an email notification } 
        {--start='.null.' : Start date, defualt is 24 hours ago } 
        {--end='.null.' : End date, default in now }';

    private $service;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->service = new SiteCheckService();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $options = [
            'publish' => $this->option('publish'),
            'start' => $this->getDate($this->option('start'), 'start'),
            'end' => $this->getDate($this->option('end'), 'end')
        ];
        $checks = $this->service->summary($options);
        
        $this->info("Summary for ". $checks['start'] . ' to ' . $checks['end'] . "\n");
        foreach ($checks['messages'] as $url => $data) {
            $this->info('Notices for ' . $url);
            foreach ($data as $message) {
                $this->info("\t$message");
            }
        } 
    }

    private function getDate($datestring, string $key) {
        if (is_null($datestring)) {
            return null;
        }
        try {
            return new Carbon($datestring);
        } catch (\Exception $e) {
            $this->error('Invaid value for '.$key);
            exit(1);
        }
    }
}
