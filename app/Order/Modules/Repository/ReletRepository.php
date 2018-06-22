<?php
/**
 * Created by PhpStorm.
 * User: wangjinlin
 * Date: 2018/5/21
 * Time: 下午4:55
 */

namespace App\Order\Modules\Repository;

use App\Order\Models\OrderRelet;
use App\Order\Modules\Inc\ReletStatus;
use Illuminate\Support\Facades\DB;

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
        if (isset($params['page']) && $params['page']>0) {
            $page = $params['page'];
        } else {
            $page = 1;
        }
        // 每页显示条数
        if (isset($params['pagesize']) && $params['pagesize']>0) {
            $pagesize = $params['pagesize'];
        } else {
            $pagesize = 20;
        }
        $offset     = ($page - 1) * $pagesize;

//        DB::connection()->enableQueryLog();
        //查询
        $result =  OrderRelet::query()
            ->where($whereArray)
            ->select('order_relet.*')
            ->offset($offset)
            ->limit($pagesize)
            ->get();

//        dd(DB::getQueryLog());
        dd($result);
        die;
        if (!$result) return false;

        return $result->toArray();


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
    public static function createRelet($data){
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

}