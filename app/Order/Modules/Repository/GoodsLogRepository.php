<?php
namespace App\Order\Modules\Repository;

use App\Order\Models\GoodsLog;

class GoodsLogRepository
{

    protected $goodsLog;

    /**
     * 记录订单日志
     * @param $data 插入数据
	 * $data = [<br/>
	 *		'order_no' => '', 【必须】 //订单编号<br/>
	 *		'action' => '', 【必须】 //操作节点名称【自己定义】<br/>
	 *		'business_key' => '', 【必须】 //业务类型<br/>
	 *		'business_no' => '', 【必须】 //业务编号<br/>
	 *		'goods_no' => '', 【必须】 //设备编号<br/>
	 *		'operator_id' => '', 【可选】 //操作人id<br/>
	 *		'operator_name' => '', 【可选】 //操作人名称<br/>
	 *		'operator_type' => '', 【可选】 //操作人类型【参照常量\App\Lib\PublicInc::Type_*】<br/>
	 *		'msg' => '', 【可选】 //操作备注<br/>
	 * ]
	 * @param $isCrontab 是否系统定时任务【默认false】【传入true时：可选参数会被重置】
     * @return mixed
     */
    public static function add( $data, $isCrontab = false ){
		if( $isCrontab ){
			$data['operator_id'] = 0;
			$data['operator_name'] = '自动执行';
			$data['operator_type'] = \App\Lib\PublicInc::Type_System;
			$data['msg'] = '系统定时任务';
		}
		$data = filter_array($data, [
            'order_no'=>'required',
            'action'=>'required',
            'business_key'=>'required',
            'business_no'=>'required',
            'goods_no'=>'required',
            'operator_id'=>'required',
            'operator_name'=>'required',
            'operator_type'=>'required',
            'msg'=>'required',
		]);
		if( count($data) < 8 ){
			return false;
		}
		$data['create_time'] =time();
        return GoodsLog::insertGetId($data);
    }

    /**
     * heaven
     * 获取订单日志
     * @param $orderNo 订单号
     * @return array|bool
     */
    public static function getLog($businessNo)
    {
        if (empty($businessNo)) return false;
        $result = GoodsLog::query()->where([
            ['businessNo', '=', $businessNo],
        ])->get()->toArray();
        return $result ?? false;
    }


}