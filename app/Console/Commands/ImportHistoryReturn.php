<?php
/**
 *
 *  历史退货退款导入接口
 *  author: heaven
 *  date  : 2018-06-12
 *
 */

namespace App\Console\Commands;

use App\Order\Models\OrderReturn;
use function GuzzleHttp\Psr7\str;
use Illuminate\Console\Command;

class ImportHistoryReturn extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:ImportHistoryReturn {param1?} {--param2=} {param3?} {--param4=}';

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
        /**
         * 入口方法
         * 支持传多个参数查询
         * e.g: php artisan command:ImportHistoryReturn 1 --param2=return_id 1 --param4=business_key
         */
        //
        try {

            echo 'start ' . date("Y-m-d H:i:s", time()) . "\n";
            //每次处理数据的条数
            $size = 200;
            // 不指定参数名的情况下用argument
            $param1 = $this->argument('param1');
            // 用--开头指定参数名
            $param2 = $this->option('param2');

            // 不指定参数名的情况下用argument
            $param3 = $this->argument('param3');
            // 用--开头指定参数名
            $param4 = $this->option('param4');

            //有参数
            $sql = '';
            if(!empty($param2) && in_array($param2, $this->getTableField()) && !empty($param1)) {
                $sql = "SELECT * FROM zuji_order2_return WHERE {$param2} = {$param1}";
            }

            if(!empty($param4) && in_array($param4, $this->getTableField()) && !empty($param3)) {
                $sql .= " AND {$param4} = {$param3}";
            }

            if (empty($param1)) {

                $sql = "SELECT * FROM zuji_order2_return";
            }

            $sql.= " ORDER BY return_id ASC";

            $returnCount   =  $this->conn->select($sql)->count();
            $offset = 0;
            //页数
            $page = ceil($returnCount/$size);

            while (true) {

                $sql.= " LIMIT({$offset}, {$size})";
                $datas   =  $this->conn->select($sql);
                $newData = objectToArray($datas);
                if (empty($newData)) {
                    echo 'no data';
                    echo 'end ' . date("Y-m-d H:i:s", time()) . "\n";exit;
                }

                foreach($newData as $keys=>$values) {
                    $this->insertSelectReturn($values);
                }
                $offset += $size;
                echo "offset".$offset."\n";
                if ($offset>$returnCount) {
                    echo 'end ' . date("Y-m-d H:i:s", time()) . "\n";exit;
                }

            }

            echo 'end ' . date("Y-m-d H:i:s", time()) . "\n";


        }   catch (\Exception $exception) {

            echo $exception->getMessage() . "\n";

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

    /**
     * 插入历史数据到新退货表
     * Author: heaven
     * @param $data 历史数据结果集
     * @return bool|\Illuminate\Database\Eloquent\Model
     */
    private function insertSelectReturn($data)
    {


//        0 => array:16 [
//            "return_id" => 1
//            "business_key" => 1
//            "order_id" => 20
//            "order_no" => "2017121800068"
//            "user_id" => 12
//            "goods_id" => 20
//            "loss_type" => 1
//            "address_id" => 1
//            "reason_id" => 3
//            "reason_text" => ""
//            "return_status" => 3
//            "admin_id" => "1"
//            "return_check_remark" => "23322342"
//            "return_check_time" => 1513595312
//            "create_time" => 1513595257
//            "update_time" => 1513595312
//  ]


        $data = [
            'goods_no'      => $goods_info['goods_no'],
            'order_no'      => $data['order_no'],
            'business_key' => $params['business_key'],
            'loss_type'     => $data['loss_type'],
            'reason_id'     => $data['reason_id'],
            'reason_text'   => $data['reason_text'],
            'user_id'       => $data['user_id'],
            'status'        => ReturnStatus::ReturnCreated,
            'refund_no'     => create_return_no(),
            'create_time'  => $data['create_time'],
            'check_time'  => $data['return_check_time'],
            'update_time'  => $data['update_time'],

        ];
        $succsss = OrderReturn::updateOrCreate($data);
         return $succsss ?? false;


    }


    /**
     * 退货原因映射
     * Author: heaven
     */
    private function reasonIdMap()
    {



    }


    /**
     * 退货状态映射
     * Author: heaven
     */
    private function returnStatusMap()
    {



    }


    /**
     * 业务类型映射
     * Author: heaven
     */
    private function businessKeyMap()
    {



    }

}
