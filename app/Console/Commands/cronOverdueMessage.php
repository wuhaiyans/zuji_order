<?php

namespace App\Console\Commands;

use App\Order\Controllers\Api\v1\CronController;
use Illuminate\Console\Command;

class cronOverdueMessage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:cronOverdueMessage {--day=}';

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
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $day = intval($this->option('day'));
        CronController::cronOverdueMessage($day);
    }

}
