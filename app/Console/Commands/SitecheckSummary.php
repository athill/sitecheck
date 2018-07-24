<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Services\SitecheckService;

class SitecheckSummary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sitecheck:summary';

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
        $checks = $this->service->summary();
        
        $this->info("Summary for ". $checks['start'] . ' to ' . $checks['end'] . "\n");
        foreach ($checks['messages'] as $url => $data) {
            $this->info('Notices for ' . $url);
            foreach ($data as $message) {
                $this->info("\t$message");
            }
        } 
    }
}
