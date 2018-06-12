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
            $sql = "select * from zuji_order2_return where {$param2} = {$param1} order by return_id asc";
        } else {
            $sql = "select * from zuji_order2_return order by return_id asc";
        }
        $datas   =  $this->conn->select($sql);
        $newData = objectToArray($datas);
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

    private function insertSelectReturn($param)
    {


        0 => array:16 [
            "return_id" => 1
            "business_key" => 1
            "order_id" => 20
            "order_no" => "2017121800068"
            "user_id" => 12
            "goods_id" => 20
            "loss_type" => 1
            "address_id" => 1
            "reason_id" => 3
            "reason_text" => ""
            "return_status" => 3
            "admin_id" => "1"
            "return_check_remark" => "23322342"
            "return_check_time" => 1513595312
            "create_time" => 1513595257
            "update_time" => 1513595312
  ]


        $data = [
            'goods_no'      => $goods_info['goods_no'],
            'order_no'      => $goods_info['order_no'],
            'business_key' => $params['business_key'],
            'loss_type'     => $params['loss_type'],
            'reason_id'     => $params['reason_id'],
            'reason_text'   => $params['reason_text'],
            'user_id'       => $params['user_id'],
            'status'        => ReturnStatus::ReturnCreated,
            'refund_no'     => create_return_no(),
            'create_time'  => time(),
        ];
        $create = OrderReturnRepository::createReturn($data);


    }
}
