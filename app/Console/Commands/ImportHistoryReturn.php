<?php
/**
 *
 *  历史退货退款导入接口
 *  author: heaven
 *  date  : 2018-06-12
 *
 */

namespace App\Console\Commands;

use function GuzzleHttp\Psr7\str;
use Illuminate\Console\Command;

class ImportHistoryReturn extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:ImportHistoryReturn {param1?} {--param2=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    //数据库连接对象
    protected $conn = '';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->conn = \DB::connection('mysql_01');
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // 入口方法
        // 不指定参数名的情况下用argument
        $param1 = $this->argument('param1');
        // 用--开头指定参数名
        $param2 = $this->option('param2');

        //有参数
        if(!empty($param2) && in_array($param2, $this->getTableField()) && !empty($param1)) {

            sql_profiler();
            $datas   =    $this->conn->select('select * from zuji_order2_return where $param2 = ?', [$param1]);
            p($datas);
            $newData = objectToArray($datas);
            dd($newData);
        }
    }


    /**
     * 获取表的字段名
     * Author: heaven
     * @return array
     */
    private function getTableField()
    {
        $fields = $this->conn->select('show columns from  zuji_order2_return');
        return array_column(objectToArray($fields),"Field");
    }
}
