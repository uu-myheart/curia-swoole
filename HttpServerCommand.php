<?php

namespace Curia\Swoole;

use Illuminate\Console\Command;

class HttpServerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'swoole:http {start}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start Swoole Http Server';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->checkEnvironment();

        $this->start();
    }

    /**
     * Check if swoole extension is loaded
     *
     * @return void
     */
    protected function checkEnvironment()
    {
        if (! extension_loaded('swoole')) {
            $this->error('Can\'t detect Swoole extension installed.');

            exit(1);
        }
    }

    /**
     * Start swoole http server
     *
     * @return void
     */
    protected function start()
    {
        $this->info('Prepare to start swoole http server');

        $appType = $this->laravel instanceof \Laravel\Lumen\Application
            ? 'lumen'
            : 'laravel';

        $this->laravel->get(Server::class)->setAppType($appType)->start();
    }
}
