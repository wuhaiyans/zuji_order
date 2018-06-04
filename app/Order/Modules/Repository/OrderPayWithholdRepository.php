<?php
/**
 *
 * 支付阶段--签约代扣处理
 */
namespace App\Order\Modules\Repository;

use App\Order\Models\OrderPayWithhold;
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
    public static function find($userId){
        dd(333);
        if(!$userId){
            return [];
        }

        $withholdInfo = OrderPayWithhold::query()
            ->where(['user_id'=>$userId])
            ->first();
        if(!$withholdInfo){
            return [];
        }

        return $withholdInfo->toArray();
    }


    /*
     * 创建代扣签约记录
     * @param $param
     * @return bool|string
     */
    public static function create($param){
        if(!$param){
            return false;
        }
        if(empty($param['withhold_no'])){
            return false;
        }
        if(empty($param['out_withhold_no'])){
            return false;
        }
        if(empty($param['user_id'])){
            return false;
        }

        $data = [
            'withhold_no'       => $param['withhold_no'],       //代扣协议码
            'out_withhold_no'   => $param['out_withhold_no'],   //支付系统代扣协议码
            'withhold_status'   => OrderPayWithholdStatus::SIGN, //状态：1：已签约；2：已解约',
            'uid'               => $param['user_id'],           //用户ID
            'sign_time'         => time(),                      //签约时间
        ];

        $success = OrderPayWithhold::create($data);
        if(!$success){
            return false;
        }
        return $success;
    }

    /*
     * 解约
     * @param $param
     * @return bool|string
     */
    public static function unsign($userId){
        if(!$userId){
            return false;
        }

        $data = [
            'withhold_no'       => "",   //代扣协议码
            'out_withhold_no'   => "",   //支付系统代扣协议码
            'withhold_status'   => OrderPayWithholdStatus::UNSIGN, //状态：1：已签约；2：已解约',
            'unsign_time'       => time(),                      //签约时间
        ];
        $success = OrderPayWithhold::where(['uid'=>$userId])->update($data);
        if(!$success){
            return false;
        }
        return true;
    }

    /*
     * 增加代扣协议使用次数
     * @param $param
     * @return bool|string
     */
    public static function add_counter($userId){
        if(!$userId){
            return false;
        }

        $num = OrderPayWithhold::where(['uid'=>$userId])
            ->increment('counter');
        if(!$num){
            return false;
        }
        return true;
    }


}