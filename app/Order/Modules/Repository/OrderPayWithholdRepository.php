<?php
/**
 *
 * 支付阶段--签约代扣处理
 */
namespace App\Order\Modules\Repository;

use App\Order\Models\OrderPayWithhold;
use App\Order\Models\OrderPayWithholdModel;
use App\Order\Modules\Inc\OrderPayWithholdStatus;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Profiler;

class OrderPayWithholdRepository
{

    /*
     * 查看代扣签约状态
     * @param $param
     * @return bool|string
     */
    public static function find($userId,$channel){
        if(!$userId){
            return [];
        }

        $withholdInfo = OrderPayWithholdModel::query()
            ->where(['user_id'=>$userId, 'withhold_channel'=>$channel])
            ->first();
        if(!$withholdInfo){
            return [];
        }

        return $withholdInfo->toArray();
    }


}