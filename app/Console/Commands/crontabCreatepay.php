<?php

namespace App\Console\Commands;

use App\Order\Controllers\Api\v1\WithholdController;
use Illuminate\Console\Command;

class crontabCreatepay extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:crontabCreatepay {--minId=} {--maxId=}';

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
        $minId = intval($this->option('minId'));
        $maxId   = intval($this->option('maxId'));

        WithholdController::crontabCreatepay($minId,$maxId);
    }

}
