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
//        try {

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
            $returnSql = "SELECT count(*) as num FROM zuji_order2_return";
            $returnCount   =  $this->conn->select($returnSql);
            $returnCount = objectToArray($returnCount);
            $returnCount    = $returnCount[0]['num'];
            $offset = 0;
            //页数
            $page = ceil($returnCount/$size);

            while (true) {
                $sql.= " LIMIT {$offset}, {$size}";
                $datas   =  $this->conn->select($sql);
                $newData = objectToArray($datas);
                if (empty($newData)) {
                    echo 'no data';
                    echo 'end ' . date("Y-m-d H:i:s", time()) . "\n";exit;
                }

                foreach($newData as $keys=>$values) {

                    $success = $this->insertSelectReturn($values);
                    if (!$success) {
                        echo 'error ' . date("Y-m-d H:i:s", time()) . "\n";
                        $errorReturnArr = $values[$values['return_id']];
                    }
                }
//                echo 2344;exit;
                $offset += $size;
                echo "offset".$offset."\n";
                if ($offset>$returnCount) {
                    echo 'end ' . date("Y-m-d H:i:s", time()) . "\n";exit;
                }
                if ($returnCount%$size==0) {
                    sleep(3000);
                }

            }

            echo 'end ' . date("Y-m-d H:i:s", time()) . "\n";
            echo '错误的记录列表：'.json_encode($errorReturnArr);

//        }   catch (\Exception $e) {
//
//            echo $e->getMessage() . "\n";
//
//        }

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

        if ($data['return_status']==6) {
            $bussness_key = 3;
        } else {
            $bussness_key = $this->businessKeyMap()[$data['business_key']];
        }

        $orderReturnData = new OrderReturn();

//        $whereArray[] = ['refund_no', '=', $data['return_id']];
//        sql_profiler();
//        $orderReturnData =  OrderReturn::where($whereArray)->first();
        $orderReturnData->goods_no = $data['goods_id'];
        $orderReturnData->order_no = $data['order_no'];
        $orderReturnData->business_key = $bussness_key;
        $orderReturnData->loss_type = $data['loss_type'];
        $orderReturnData->reason_id = $data['reason_id'];
        $orderReturnData->reason_text = $data['reason_text'];
        $orderReturnData->user_id = $data['user_id'];
        $orderReturnData->status = $this->returnStatusMap()[$data['return_status']];
        $orderReturnData->refund_no = $data['return_id'];
        $orderReturnData->remark = $data['return_check_remark'];
        $orderReturnData->create_time = $data['create_time'];
        $orderReturnData->check_time = $data['return_check_time'];
        $orderReturnData->update_time = $data['update_time'];
        $succsss = $orderReturnData->save();

        return $succsss;

    }


    /**
     * 退货状态映射
     * Author: heaven
     */
    private function returnStatusMap()
    {

             return [
                 1=>1,
                 2=>1,
                 3=>2,
                 4=>3,
                 5=>4,
                 6=>2,
             ];
    }

    /**
     * 业务类型映射
     * Author: heaven
     */
    private function businessKeyMap()
    {
            return [
                1=>2,
            ];
    }

}
