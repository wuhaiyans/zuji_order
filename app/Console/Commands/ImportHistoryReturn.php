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


                    $success = $this->insertSelectReturn($values);
                    if (!$success) {
                        echo '导入退货error ' . date("Y-m-d H:i:s", time()) . "\n";
                        $errorReturnArr = $values[$values['return_id']];
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
                    sleep(3000);
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

        $receiveSql = "SELECT *  FROM zuji_order2_receive WHERE order_no={$orderNo}";
        $returnData  =  $this->conn->select($receiveSql);
        return objectToArray($returnData) ? objectToArray($returnData)[0]: '';
    }


    /**
     * 获取检测信息
     * Author: heaven
     * @param $orderNo
     * @return mixed
     */
    private function getEvaluationInfo($orderNo){

        $receiveSql = "SELECT *  FROM zuji_order2_evaluation WHERE order_no={$orderNo}";
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

        if ($data['return_status']==6) {
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

        $datas += [
            'goods_no'      => $data['goods_id'],
            'order_no'      => $data['order_no'],
            'business_key' => $bussness_key,
            'loss_type'     => $data['loss_type'],
            'reason_id'     => $data['reason_id'],
            'reason_text'   => $data['reason_text'],
            'user_id'       => $data['user_id'],
            'status'        => $this->returnStatusMap()[$data['return_status']],
            'refund_no'  =>  createNo(2),
            'old_refund_id' => $data['return_id'],
            'remark'        => $data['return_check_remark'],
            'create_time'  => $data['create_time'],
            'check_time'  => $data['return_check_time'],
            'update_time'  => $data['update_time'],

        ];
        if ($orderRefundData) unset($datas['refund_no']);
        $succsss = OrderReturn::updateOrCreate($datas);
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
