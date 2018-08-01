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
     * @params  $data  业务参数（检测参数）
     * [
     *  [
     *      'goods_no'          => '',//商品编号                        string    【必传】
     *      'evaluation_status' => '',//检测状态【1：合格；2：不合格】  int    【必传】
     *      'evaluation_time'   => '',//检测时间（时间戳）              int       【必传】
     *      'evaluation_remark' => '',//检测备注                        string   【可选】【检测不合格时必有】
     *      'compensate_amount' => '',//赔偿金额                        string   【可选】【检测不合格时必有】
     *  ],
     *  [
     *      'goods_no'          => '',//商品编号                        string    【必传】
     *      'evaluation_status' => '',//检测状态【1：合格；2：不合格】  int    【必传】
     *      'evaluation_time'   => '',//检测时间（时间戳）              int      【必传】
     *      'evaluation_remark' => '',//检测备注                        string   【可选】【检测不合格时必有】
     *      'compensate_amount' => '',//赔偿金额                        string   【可选】【检测不合格时必有】
     *  ],
     * ]
     * @params  $business_key  业务参数
     *  'business_key '  =>''   int   业务类型   【必传】
     *
     * @param array $userinfo 用户信息参数
     * [
     *      'uid'    =>''     用户id      int      【必传】
     *      'username' =>''   用户名      string   【必传】
     *      'type'    =>''   渠道类型     int      【必传】  1  管理员，2 用户，3 系统自动化
     * ]
     */

    public static function checkResult(array $data,int $business_key,array $userinfo)
    {

        $params['business_key']=$business_key;
        $params['data']=$data;
        $params['userinfo']=$userinfo;
        if( self::request(\config('app.APPID'), \config('ordersystem.ORDER_API'),'api.Return.isQualified', '1.0', $params) ){
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