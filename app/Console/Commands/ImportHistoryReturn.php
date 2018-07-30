<?php
/**
 *
 *  历史退货导入接口
 *  author: heaven
 *  date  : 2018-06-13
 *
 */

namespace App\Console\Commands;

use App\Lib\Common\LogApi;
use App\Order\Models\OrderGoods;
use App\Order\Models\OrderReturn;
use function GuzzleHttp\Psr7\str;
use Illuminate\Console\Command;
use ToQueue\loginLog;

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
         * e.g: php artisan command:ImportHistoryReturn 1 --param2=return_id 1 --param4=business_key
         */
        //
        try {

            echo '导入退货start ' . date("Y-m-d H:i:s", time()) . "\n";
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

            $bar = $this->output->createProgressBar($returnCount); //开始

            while (true) {
                $sql.= " LIMIT {$offset}, {$size}";
                $datas   =  $this->conn->select($sql);
                $newData = objectToArray($datas);
                if (empty($newData)) {
                    LogApi::info("导入退货no data");
                    echo '导入退货no data';
                    echo 'end ' . date("Y-m-d H:i:s", time()) . "\n";exit;
                }

                foreach($newData as $keys=>$values) {

//                    if (!ImportOrder::isAllowImport($values['order_no'])){
//
//                        $continueReturnArr[] = $values['return_id'];
//                        continue;
//                    }
                    $success = $this->insertSelectReturn($values);
                    //更新order_goods表记录

                    if (!$success) {
                        echo '导入退货error ' . date("Y-m-d H:i:s", time()) . "\n";
                        $errorReturnArr[] = $values['return_id'];
                    } else {

                        $bar->advance();  //中间
                    }
                }
                $offset += $size;
                LogApi::info("导入退货offset".$offset);
                echo "导入退货offset".$offset."\n";
                if ($offset>$returnCount) {
                    LogApi::info('导入退货end ' . date("Y-m-d H:i:s", time()));
                    echo '导入退货end ' . date("Y-m-d H:i:s", time()) . "\n";exit;
                }
                if ($returnCount%$size==0) {
                    sleep(3);
                }

            }
            $bar->finish(); //结束
            LogApi::info('导入退货end ' . date("Y-m-d H:i:s", time()) );
            LogApi::info('导入退货错误的记录列表：'.json_encode($errorReturnArr));
            echo '导入退货end ' . date("Y-m-d H:i:s", time()) . "\n";
            echo '导入退货错误的记录列表：'.json_encode($errorReturnArr);

        }   catch (\Exception $e) {

            LogApi::info('导入退货异常：'.$e->getMessage());
            echo '导入退货异常：'.$e->getMessage() . "\n";

        }

    }


    /**
     * 获取收货信息
     * Author: heaven
     * @param $orderNo
     * @return mixed
     */
    private function getReceiveInfo($orderNo){

        $receiveSql = "SELECT *  FROM zuji_order2_receive WHERE order_no='{$orderNo}'";
        $returnData  =  $this->conn->select($receiveSql);
        return objectToArray($returnData) ? objectToArray($returnData)[0]: '';
    }


    private function setOrderGoodsStatus($orderNo,$bussness_key,$returnStatus,$refundNo)
    {
        $orderGoodStatusList = $this->returnOrderGoodsStatusMap($bussness_key);
        $orderGoodsStatu = $orderGoodStatusList[$returnStatus];
        return OrderGoods::where([
            ['order_no', '=', $orderNo],
        ])->update(['goods_status'=>$orderGoodsStatu,'business_key'=>$bussness_key, 'business_no'=>$refundNo]);

    }


    /**
     * 获取检测信息
     * Author: heaven
     * @param $orderNo
     * @return mixed
     */
    private function getEvaluationInfo($orderNo){

        $receiveSql = "SELECT *  FROM zuji_order2_evaluation WHERE order_no='{$orderNo}'";
        $evaluationData   =  $this->conn->select($receiveSql);
        return objectToArray($evaluationData)? objectToArray($evaluationData)[0]: '' ;
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
     * 插入历史数据到新退货表
     * Author: heaven
     * @param $data 历史数据结果集
     * @return bool|\Illuminate\Database\Eloquent\Model
     */
    private function insertSelectReturn($data)
    {

        LogApi::info('导入退货数据：',$data);

        if ($data['return_status']==6 || $data['reason_id']==6) {
            $bussness_key = 3;
        } else {
            $bussness_key = $this->businessKeyMap()[$data['business_key']];
        }
        $whereArray[] = ['order_no', '=', $data['order_no']];
        $orderRefundData =  OrderReturn::where($whereArray)->first();

        //获取检测信息
        $evaluationData = $this->getEvaluationInfo($data['order_no']);
        $datas = array();
        if ($evaluationData) {

            $datas = [
            'evaluation_status' =>  $evaluationData['evaluation_status'],
            'evaluation_remark' =>  $evaluationData['evaluation_remark'],
            'evaluation_time'   =>  $evaluationData['evaluation_time'],
            ];

        }
        //获取收货信息
        $receiveData = $this->getReceiveInfo($data['order_no']);
        if ($receiveData) {
            $datas += [
                'receive_no' => $receiveData['receive_id'],
                'logistics_id' =>   $receiveData['wuliu_channel_id'],
                'logistics_no' =>   $receiveData['wuliu_no'],
            ];
        }

        $refundNo   =   createNo(2);
        $datas += [
            'goods_no'      => $data['goods_id'],
            'order_no'      => $data['order_no'],
            'business_key' => $bussness_key,
            'loss_type'     => $data['loss_type'],
            'reason_id'     => $data['reason_id'],
            'reason_text'   => $data['reason_text'],
            'user_id'       => $data['user_id'],
            'status'        => $this->returnStatusMap()[$data['return_status']],
            'refund_no'  =>  $refundNo,
            'old_refund_id' => $data['return_id'],
            'remark'        => $data['return_check_remark'],
            'create_time'  => $data['create_time'],
            'check_time'  => $data['return_check_time'],
            'update_time'  => $data['update_time'],
        ];
        LogApi::info('导入退货数据的$orderRefundData：',$orderRefundData);
        if ($orderRefundData) unset($datas['refund_no']);
        LogApi::info('导入退货数据的参数：',$datas);
        $succsss = OrderReturn::updateOrCreate($datas);
        //更新退货退款表记录
        $this->setOrderGoodsStatus($data['order_no'],$bussness_key,$data['return_status'],$refundNo);
        return $succsss;

    }


    /**
     * 导入退货状态映射
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
     * 导入orderGoods表的记录
     * Author: heaven
     */
    private function returnOrderGoodsStatusMap($businessKey)
    {
//        0：非启用；10： 租机中； 20：退货中 ，21 ：已退货； 30：  换货中， 31：已换货 ；40 ：还机中， 41：还机完成；50：买断中，
//        51：买断完成； 60： 续租中， 61：续租完成；，71：已退款
//
//    0初始化 1提交申请 2同意 3审核拒绝 4已取消 5已收货 7退货完成 8换货完成 9已退款 10退款中 11换货已发货
        //换货 $businessKey 3               2是退货
        if ($businessKey==3) {
            return [
                1=>30,
                2=>30,
                3=>30,
                4=>10,
                5=>10,
                6=>30,
            ];
        } else {

            return [
                1=>20,
                2=>20,
                3=>20,
                4=>10,
                5=>10,
                6=>20,
            ];
        }

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
