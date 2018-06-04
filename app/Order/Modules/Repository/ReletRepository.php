<?php
/**
 * Created by PhpStorm.
 * User: wangjinlin
 * Date: 2018/5/21
 * Time: 下午4:55
 */

namespace App\Order\Modules\Repository;

use App\Lib\Common\LogApi;
use App\Order\Models\OrderRelet;
use App\Order\Modules\Inc\OrderGoodStatus;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Inc\ReletStatus;
use Illuminate\Support\Facades\DB;
use App\Order\Models\OrderGoods;
use App\Order\Models\OrderGoodsUnit;

class ReletRepository
{
    protected $orderRelet;

    public function __construct(OrderRelet $orderRelet)
    {
        $this->orderRelet = $orderRelet;
    }

    /**
     * 获取续租列表
     *
     * @params[
     *      user_id=>用户ID(选填),
     *      status=>状态(选填),
     *      pages=>页数(选填),
     *      pagesize=>每页显示条数(选填),
     * ]
     * @return [
     *  [
     *      id=>id,
     *      user_id=>,
     *      zuqi_type=>类型：1长租（月）；2短租（天）,
     *      zuqi=>租期,
     *      order_no=>订单编号,
     *      create_time=>下单时间,
     *      out_trade_no=>第三方流水号,
     *      trade_no=>交易流水号,
     *      pay_type=>支付方式及渠道,
     *      user_name=>用户名,
     *      user_phone=>手机号,
     *      goods_id=>续租商品ID,
     *      relet_amount=>续租金额,
     *      status=>状态,
     *  ]
     * ]
     */
    public function getList($params){
        //拼接 页数 搜索参数 每页显示数
        $whereArray = [];

        //根据用户id
        if (isset($params['user_id']) && !empty($params['user_id'])) {
            $whereArray[] = ['order_relet.user_id', '=', $params['user_id']];
        }
        //状态
        if (isset($params['status']) && !empty($params['status'])) {
            $whereArray[] = ['order_relet.status', '=', $params['status']];
        }
        // 页数
        if ($params['page']) {
            $page = $params['page'];
        } else {
            $page = 1;
        }
        // 每页显示条数
        if ($params['pagesize']) {
            $pagesize = $params['pagesize'];
        } else {
            $pagesize = 20;
        }

        //查询
        $orderList = DB::table('order_relet')
            ->where($whereArray)
            ->select('order_relet.*')
            ->paginate($pagesize,$columns = ['*'], $pageName = 'page', $page);

        //返回
        return $orderList;

    }

    /**
     * 获取用户未完成续租列表(前段)
     *
     * @param $params[
     *      user_id 用户ID
     *      status 状态
     * ]
     * @return array
     */
    public function getUserList($params){
        return OrderRelet::query()->where([
            ['user_id','=',$params['user_id']],
            ['status','=',ReletStatus::STATUS1],
        ])->get()->toArray();

    }

    /**
     * 通过ID获取一条记录
     *
     * @params[
     *      id=>ID(必填),
     * ]
     * @return [
     *      id=>id,
     *      user_id=>,
     *      zuqi_type=>类型：1长租（月）；2短租（天）,
     *      zuqi=>租期,
     *      order_no=>订单编号,
     *      create_time=>下单时间,
     *      out_trade_no=>第三方流水号,
     *      trade_no=>交易流水号,
     *      pay_type=>支付方式及渠道,
     *      user_name=>用户名,
     *      user_phone=>手机号,
     *      goods_id=>续租商品ID,
     *      relet_amount=>续租金额,
     *      status=>状态,
     * ]
     */
    public function getRowId($params){
        return $this->orderRelet->find($params['id']);

    }

    /**
     * 设置status状态
     *      1创建,2完成,3取消
     *
     * @param $params
     * @return bool
     */
    public function setStatus($params){
//        $params['id'] = $params['id'];
//        $params['status'] = $params['status'];
        return $this->orderRelet->save($params);
    }

    /**
     * 创建续租单
     *
     * @param $params
     * @return bool
     */
    public function createRelet($data){
        return OrderRelet::insert($data);
    }

    /**
     * 通过续租编号修改支付完成状态
     */
    public static function reletPayStatus(string $reletNo,int $payStatus){
        if(empty($reletNo) || empty($payStatus)){
            return false;
        }
        $data['status'] = $payStatus;
        $data['pay_time'] =time();
        return OrderRelet::where('relet_no','=',$reletNo)->update($data);
    }

    /**
     * 修改设备表状态续租完成,新建设备周期数据
     *
     * @param $reletNo
     * @return bool
     */
    public function setGoods($reletNo){
        $b = ReletRepository::reletPayStatus($reletNo, ReletStatus::STATUS2);
        if (!$b) {
            LogApi::notify("续租修改支付状态失败", $reletNo);
            return false;
        }
        //查询
        // 续租表
        $reletRow = OrderRelet::where(['relet_no','=',$reletNo])->get(['goods_id'])->toArray();
        // 设备表
        $goodsObj = OrderGoods::where(['id'],'=',$reletRow['goods_id'])->first();
        // 设备周期表
        $goodsUnitRow = OrderGoodsUnit::where(
            ['order_no','=',$goodsObj->order_no],
            ['goods_no','=',$goodsObj->goods_no]
        )->orderBy('id','desc')->fresh()->toArray();
        //判断租期类型
        if($reletRow['zuqi_type']==OrderStatus::ZUQI_TYPE1){
            $t = $reletRow['zuqi']*(60*60*24);
        }else{
            $t = $reletRow['zuqi']*30*(60*60*24);
        }
        $data = [
            'order_no'=>$goodsObj->order_no,
            'goods_no'=>$goodsObj->goods_no,
            'user_id'=>$goodsObj->user_id,
            'unit'=>$reletRow['zuqi_type'],
            'unit_value'=>$reletRow['zuqi'],
            'begin_time'=>$goodsUnitRow['begin_time'],
            'end_time'=>$goodsUnitRow['begin_time']+$t,
        ];

        //修改订单商品状态
        if( !$goodsObj->save(['goods_status'=>OrderGoodStatus::RENEWAL_OF_RENT,'update_time'=>time()]) ){
            LogApi::notify("续租修改设备状态失败", $reletNo);
            return false;
        }
        //添加设备周期表
        if( !OrderGoodsUnit::insert($data) ){
            LogApi::notify("续租添加设备周期表失败", $reletNo);
            return false;
        }

        LogApi::notify("续租支付成功", $reletNo);
        return true;
    }

}