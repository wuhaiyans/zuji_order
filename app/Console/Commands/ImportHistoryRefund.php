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
use App\Order\Models\OrderGoods;

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
        ini_set('memory_limit','1024M');
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
            $bar = $this->output->createProgressBar($returnCount); //开始



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
//                    if (!ImportOrder::isAllowImport($values['order_no'])){
//
//                        $continueReturnArr[] = $values['refund_id'];
//                        continue;
//                    }

                    $success = $this->insertSelectReturn($values);
                    if (!$success) {
                        echo '导入退款error ' . date("Y-m-d H:i:s", time()) . "\n";
                        $errorReturnArr[] = $values['refund_id'];
                    } else {

                        $bar->advance(); //中间
                    }

                }
//                echo 2344;exit;
                $offset += $size;
                LogApi::info("导入退款offset".$offset);
                echo "导入退款offset".$offset."\n";
                if ($offset>$returnCount) {
                    LogApi::info('导入退款end ' . date("Y-m-d H:i:s", time()));
//                    echo "被过滤的列表总数".count($continueReturnArr)."列表" .json_encode($continueReturnArr);
                    echo '导入退款end ' . date("Y-m-d H:i:s", time()) . "\n";exit;
                }
                if ($returnCount%$size==0) {
                    sleep(3);
                }

            }


            $bar->finish(); //结束
            LogApi::info('导入退款end ' . date("Y-m-d H:i:s", time()) );
//            echo "被过滤的列表总数".count($continueReturnArr)."列表" .json_encode($continueReturnArr);
//            LogApi::info('导入退款错误的记录列表：'.json_encode($errorReturnArr));
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
    private function insertSelectReturn($datas)
    {
        //根据订单号查询新表是否有退货的数据，如果存在，是退货业务，并且更新新表的数据，不存在，退款业务，并且创建退款业务
        $whereArray[] = ['order_no', '=', $datas['order_no']];
        $orderRefundData =  OrderReturn::where($whereArray)->first();
//        p($orderRefundData);
        if (!$orderRefundData)
        {
            //不存在退货记录说明是退款，插入
            $bussness_key = 8;
        } else {
            //存在是退货，更新记录
            $bussness_key = 2;
        }
        $refundNo   =   createNo(2);
        $refundData = [
            'refund_no'  =>  $refundNo,
            'old_refund_id' => $datas['refund_id'],
            'out_refund_no'  =>  $datas['out_refund_no'],
            'user_id'  =>  $datas['user_id'],
            'business_key' =>   $bussness_key,
            'pay_amount'  =>  normalizeNum($datas['payment_amount']/100),
            'refund_amount'  =>  normalizeNum($datas['should_amount']/100),
            'goods_no'  =>  $datas['goods_id'],
            'status'    =>  $this->refundStatusMap()[$datas['refund_status']],
            'create_time'  =>  $datas['create_time'],
            'update_time'  =>  $datas['refund_time'],
            'order_no'  =>  $datas['order_no'],
        ];

         if ($orderRefundData){

             unset($refundData['refund_no']);
             $this->setOrderGoodsStatus($datas['order_no'],$bussness_key,$datas['refund_status'],$refundNo);
            return  $orderRefundData->update($refundData);
         }

        $ret = OrderReturn::updateOrCreate($refundData);
        if (!$ret->getQueueableId()) {
                return false;
        }
        $this->setOrderGoodsStatus($datas['order_no'],$bussness_key,$datas['refund_status'],$refundNo);
        return true;

    }

    private function setOrderGoodsStatus($orderNo,$bussness_key,$returnStatus,$refundNo)
    {
        $orderGoodStatusList = $this->refundOrderGoodsStatusMap($bussness_key);
        $orderGoodsStatu = $orderGoodStatusList[$returnStatus];
        return OrderGoods::where([
            ['order_no', '=', $orderNo],
        ])->update(['goods_status'=>$orderGoodsStatu,'business_key'=>$bussness_key, 'business_no'=>$refundNo]);

    }

    /**
     * 导入orderGoods表的记录
     * Author: heaven
     */
    private function refundOrderGoodsStatusMap($businessKey)
    {
//        0：非启用；10： 租机中； 20：退货中 ，21 ：已退货； 30：  换货中， 31：已换货 ；40 ：还机中， 41：还机完成；50：买断中，
//        51：买断完成； 60： 续租中， 61：续租完成；，71：已退款
//
//    0初始化 1提交申请 2同意 3审核拒绝 4已取消 5已收货 7退货完成 8换货完成 9已退款 10退款中 11换货已发货
        //换货 $businessKey 3               2是退货
        if ($businessKey==2) {
            return [
                1=>20,
                2=>20,
                3=>20,
                4=>21,
                5=>20,
            ];
        } else {

            return [
                1=>70,
                2=>70,
                3=>70,
                4=>71,
                5=>70,
            ];
        }

    }


    /**
     * 导入退款状态映射
     * Author: heaven
     */
    private function refundStatusMap()
    {
             return [
                 1=>1,
                 2=>1,
                 3=>10,
                 4=>9,
                 5=>10,
             ];
    }


}
