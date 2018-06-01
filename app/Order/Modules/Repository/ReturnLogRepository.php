<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/5/29 0029
 * Time: 下午 4:17
 */

namespace App\Order\Modules\Repository;
use App\Order\Models\ReturnLog;
use Illuminate\Support\Facades\DB;
class ReturnLogRepository
{

    /**
     * 记录退货、换货、退款日志
     * @param $business_type 业务类型
     * @param $business_status 业务状态
     *
     */
    public static function add($business_type,$business_status ){
        $ReturnLog=new ReturnLog();
        $data =[
            'business_type'=>$business_type,
            'business_status'=>$business_status,
            'create_time'=>time(),
        ];
        $log =$ReturnLog::query()->insert($data);
        return $log->getQueueableId();
    }



}