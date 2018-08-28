<?php

namespace App\Console\Commands;

use App\Order\Modules\OrderExcel\CronCollection;
use App\Order\Modules\OrderExcel\CronOperator;
use Illuminate\Console\Command;

class EvermonthCollection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:EvermonthCollection';

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
        CronCollection::everMonth();
        echo "success";
    }
}
