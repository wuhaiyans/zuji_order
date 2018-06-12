<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestCreate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:daoru';

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
        $datas01 = \DB::connection('mysql_01')->table('zuji_order2')->select('*')->first();
        $a=objectToArray($datas01);
        var_dump($a);

    }
    public static function list($limit, $page=1)
    {
        return KnightInfo::paginate($limit, ['*'], 'page', $page);
    }
}
