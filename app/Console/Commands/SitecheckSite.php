<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Services\SitecheckService;

class SitecheckSite extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sitecheck:site {site} {--p|publish} {--s|save}';

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
        $this->service = new SiteCheckService(explode(' ', $this->signature[0]));
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $url = $this->argument('site');
        $publish = $this->option('publish');
        $save = $this->option('save');        
        $sites = $this->service->notifications([ $url ], $save, $publish);

        foreach ($sites as $url => $data) {
            $this->info('Notices for ' . $url);
            foreach ($data['messages'] as $message) {
                $this->info("\t$message");
            }
        }
    }
}
