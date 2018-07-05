<?php
namespace App\Lib\Order;
use App\Lib\Common\LogApi;
use App\Lib\Curl;
use Illuminate\Support\Facades\Log;
/**
 * Class Delivery
 * 与退换货相关
 */
class ReturnGoods extends \App\Lib\BaseApi
{

    /**
     * 检查项反馈
     *
     * [
     *  [
     *      'goods_no' => '',//商品编号<br/>
     *      'evaluation_status' => '',//检测状态【必须】【1：合格；2：不合格】<br/>
     *      'evaluation_time' => '',//检测时间（时间戳）【必须】<br/>
     *      'evaluation_remark' => '',//检测备注【可选】【检测不合格时必有】<br/>
     *      'compensate_amount' => '',//赔偿金额【可选】【检测不合格时必有】<br/>
     *  ],
     *  [
     *      'goods_no' => '',//商品编号<br/>
     *      'evaluation_status' => '',//检测状态【必须】【1：合格；2：不合格】<br/>
     *      'evaluation_time' => '',//检测时间（时间戳）【必须】<br/>
     *      'evaluation_remark' => '',//检测备注【可选】【检测不合格时必有】<br/>
     *      'compensate_amount' => '',//赔偿金额【可选】【检测不合格时必有】<br/>
     *  ],
     * ]
     *
     */

    public static function checkResult($data,$business_key)
    {

        $params['business_key']=$business_key;
        $params['data']=$data;
        if( self::request(\env('APPID'), \config('ordersystem.ORDER_API'),'api.Return.isQualified', '1.0', $params) ){
            return true;
        }

        /*try{
            $base_api = config('ordersystem.ORDER_API');
            $response = Curl::post($base_api,json_encode([
                'appid'=> 1,
                'version' => 1.0,
                'method'=> 'api.Return.isQualified',//模拟
                'params' => ['business_key'=>$business_key,'data'=>$data]
            ]));
            $res = json_decode($response,true);
            if ($res->code != 0) {
                return false;
            }
        } catch (\Exception $e) {
            LogApi::error($e->getMessage());
            return false;
        }
        return true;*/

    }




}