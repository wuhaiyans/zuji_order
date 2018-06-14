<?php
/**
 *
 *  历史退款导入接口
 *  author: heaven
 *  date  : 2018-06-13
 *
 */

namespace App\Console\Commands;

use App\Lib\Common\LogApi;
use App\Order\Models\OrderReturn;
use function GuzzleHttp\Psr7\str;
use Illuminate\Console\Command;

class ImportHistoryRefund extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:ImportHistoryRefund {param1?} {--param2=} {param3?} {--param4=}';

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
         * e.g: php artisan command:ImportHistoryRefund 1 --param2=return_id 1 --param4=business_key
         */
        //
        try {

            echo '导入退款start ' . date("Y-m-d H:i:s", time()) . "\n";
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
                $sql = "SELECT * FROM zuji_order2_refund WHERE {$param2} = {$param1}";
            }

            if(!empty($param4) && in_array($param4, $this->getTableField()) && !empty($param3)) {
                $sql .= " AND {$param4} = {$param3}";
            }

            if (empty($param1)) {

                $sql = "SELECT * FROM zuji_order2_refund";
            }
            $sql.= " ORDER BY refund_id ASC";
            $returnSql = "SELECT count(*) as num FROM zuji_order2_refund";
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
                    LogApi::info("导入退款no data");
                    echo 'no data';
                    echo 'end ' . date("Y-m-d H:i:s", time()) . "\n";exit;
                }

                foreach($newData as $keys=>$values) {

                    $success = $this->insertSelectReturn($values);
                    if (!$success) {
                        echo '导入退款error ' . date("Y-m-d H:i:s", time()) . "\n";
                        $errorReturnArr = $values[$values['return_id']];
                    }
                }
//                echo 2344;exit;
                $offset += $size;
                LogApi::info("导入退款offset".$offset);
                echo "导入退款offset".$offset."\n";
                if ($offset>$returnCount) {
                    LogApi::info('导入退款end ' . date("Y-m-d H:i:s", time()));
                    echo '导入退款end ' . date("Y-m-d H:i:s", time()) . "\n";exit;
                }
                if ($returnCount%$size==0) {
                    sleep(3000);
                }

            }
            LogApi::info('导入退款end ' . date("Y-m-d H:i:s", time()) );
            LogApi::info('导入退款错误的记录列表：'.json_encode($errorReturnArr));
            echo '导入退款end ' . date("Y-m-d H:i:s", time()) . "\n";
            echo '导入退款错误的记录列表：'.json_encode($errorReturnArr);

        }   catch (\Exception $e) {

            LogApi::info('导入退款异常：'.$e->getMessage());
            echo '导入退款异常：'.$e->getMessage() . "\n";

        }

    }

    /**
     * 获取表的字段名
     * Author: heaven
     * @return array
     */
    private function getTableField()
    {
        $fields = $this->conn->select('show columns from  zuji_order2_refund');
        return array_column(objectToArray($fields),"Field");
    }

    /**
     * 插入历史数据到新退款表
     * Author: heaven
     * @param $data 历史数据结果集
     * @return bool|\Illuminate\Database\Eloquent\Model
     */
    private function insertSelectReturn($data)
    {


//        [172] => Array
//    (
//            [refund_id] => 226
//            [order_id] => 1888
//            [refund_no] => Dev2018050400041
//            [out_refund_no] => 201805040006
//            [refund_amount] => 60100
//            [user_id] => 124
//            [mobile] => 18210481360
//            [payment_amount] => 60100
//            [should_amount] => 60100
//            [should_remark] => 测试测试
//            [should_admin_id] => 0
//            [goods_id] => 1859
//            [payment_id] => 727
//            [payment_channel_id] => 4
//            [refund_status] => 4
//            [business_key] => 1
//            [reason_id] => 0
//            [refund_type] => 0
//            [account_name] =>
//            [account_no] =>
//            [really_name] =>
//            [create_time] => 1525404264
//            [update_time] => 1525404299
//            [refund_time] => 1525404299
//            [refund_remark] => 测试测试
//            [admin_id] => 0
//            [out_refund_remark] =>
//            [order_no] => Dev2018050400037
//        )


            $datas['refund_id'];
            $datas['order_id'];
            $datas['refund_no'];
            $datas['out_refund_no'];
            $datas['refund_amount'];
            $datas['user_id'];
            $datas['mobile'];
            $datas['payment_amount'];
            $datas['should_amount'];
            $datas['should_remark'];
            $datas['should_admin_id'];
            $datas['goods_id'];
            $datas['payment_id'];
            $datas['payment_channel_id'];
            $datas['refund_status'];
            $datas['business_key'];
            $datas['reason_id'];
            $datas['refund_type'];
            $datas['account_name'];
            $datas['really_name'];
            $datas['create_time'];
            $datas['update_time'];
            $datas['refund_time'];
            $datas['refund_remark'];
            $datas['admin_id'];
            $datas['out_refund_remark'];
            $datas['order_no'];

        $data = [
            'order_no'=> $data['refund_id'],
            'order_no'=> $data['refund_id'],
            'order_no'=> $data['refund_id'],
            'order_no'=> $data['refund_id'],
            'order_no'=> $data['refund_id'],
            'order_no'=> $data['refund_id'],
            'order_no'=> $data['refund_id'],
            'order_no'=> $data['refund_id'],
            'order_no'=> $data['refund_id'],
            'order_no'=> $data['refund_id'],
            'order_no'=> $data['refund_id'],
            'order_no'=> $data['refund_id'],
            'order_no'=> $data['refund_id'],
            'order_no'=> $data['refund_id'],
            'order_no'=> $data['refund_id'],
            'order_no'=> $data['refund_id'],
            'order_no'=> $data['refund_id'],
            'order_no'=> $data['refund_id'],
            'order_no'=> $data['refund_id'],
            'order_no'=> $data['refund_id'],
            'order_no'=> $data['refund_id'],
            'order_no'=> $data['refund_id'],
            'order_no'=> $data['refund_id'],
            'order_no'=> $data['refund_id'],
            'order_no'=> $data['refund_id'],
            'order_no'=> $data['refund_id'],
            'order_no'=> $data['refund_id'],
            'order_no'=> $data['refund_id'],


        ];
        $ret = OrderReturn::updateOrCreate($data);



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
     * 导入退款状态映射
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
