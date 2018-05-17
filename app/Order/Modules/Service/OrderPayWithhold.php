<?php
namespace App\Order\Modules\Service;

use App\Order\Modules\Repository\OrderPayWithholdRepository;
use Illuminate\Support\Facades\Log;
use App\Lib\ApiStatus;

class OrderPayWithhold
{

    /*
    * 查看代扣签约状态
    * @param $param
    * @return array
    */
    public static function find($userId){
        if(!$userId){
            return false;
        }

        return OrderPayWithholdRepository::find($userId);

    }

    /*
    * 创建代扣签约记录
    * @param $param
     * [
     *      'withhold_no' => ''     //代扣协议码
     *      'out_withhold_no' => '' //支付系统代扣协议码
     *      'user_id' => ''         //用户ID
     * ]
    * @return bool|string
    */
    public static function create_withhold($param){
        $param = filter_array($param, [
            'withhold_no' => 'required',
            'out_withhold_no' => 'required',
            'user_id' => 'required',

        ]);

        if(count($param) < 3){
            return false;
        }

        return OrderPayWithholdRepository::create($param);

    }

    /*
    * 代扣协议解约
    * @param $userId
    * @return bool|string
    */
    public static function unsign_withhold($userId){
        if(!$userId){
            return false;
        }
        // 判断是否允许解除代扣
        $withholdInfo = OrderPayWithholdRepository::find($userId);
        if(!$withholdInfo || $withholdInfo['counter'] != '0'){
            return ApiStatus::CODE_71010;
        }

        return OrderPayWithholdRepository::unsign($userId);
    }

    /*
    * 增加代扣协议使用次数
    * @param $userId
    * @return bool|string
    */
    public static function add_counter($userId){
        if(!$userId){
            return false;
        }
        // 判断是否允许解除代扣
        $withholdInfo = OrderPayWithholdRepository::find($userId);
        if(!$withholdInfo || $withholdInfo['counter'] == '0'){
            return ApiStatus::CODE_71004;
        }

        return OrderPayWithholdRepository::add_counter($userId);
    }

}