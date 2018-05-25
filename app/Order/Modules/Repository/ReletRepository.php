<?php
/**
 * Created by PhpStorm.
 * User: wangjinlin
 * Date: 2018/5/21
 * Time: 下午4:55
 */

namespace App\Order\Modules\Repository;

use App\Order\Models\OrderRelet;
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
        return $this->orderRelet->find($params['id'])->toArray();

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
    public function createRelet($params){
        $time = time();
        $params['create_time'] = $time;
        $params['status'] = 1;
        return $this->orderRelet->save($params);
    }

}