<?php
namespace App\Order\Modules\Repository\Relet;

use App\Order\Models\OrderRelet;
use App\Order\Modules\Inc\ReletStatus;

/**
 * 续租公共服务类
 * User: wangjinlin
 * Date: 2018/6/5
 * Time: 下午4:24
 */
class Relet
{
    /**
     *
     * @var OrderRelet
     */
    private $model = [];

    /**
     * 构造函数
     * @param array $data 订单原始数据
     */
    public function __construct( OrderRelet $model ) {
        $this->model = $model;
    }

    /**
     * 读取商品原始数据
     * @return array
     */
    public function getData():array{
        return $this->model->toArray();
    }

    /**
     * 修改续租状态 完成
     */
    public function setStatusOn(){
        if($this->model->status == ReletStatus::STATUS1){
            $this->model->status = ReletStatus::STATUS2;
            return $this->model->save();
        }else{
            return false;
        }

    }

    /**
     * 修改续租状态 取消
     */
    public function setStatusOff(){
        if($this->model->status == ReletStatus::STATUS1){
            $this->model->status = ReletStatus::STATUS3;
            return $this->model->save();
        }else{
            return false;
        }

    }

    /**
     * 获取支付链接
     *
     */
    public static function getPayUrl(){}


    //-+------------------------------------------------------------------------
    // | 静态方法
    //-+------------------------------------------------------------------------

    /**
     * 获取商品
     * <p>当订单不存在时，抛出异常</p>
     * @param int   	$id		    ID
     * @param int		$lock		锁
     * @return \App\Order\Modules\Repository\Relet\Relet
     * @return  bool
     */
    public static function getByReletId( int $id, int $lock=0 ) {
        $builder = OrderRelet::where([
            ['id', '=', $id],
        ])->limit(1);
        if( $lock ){
            $builder->lockForUpdate();
        }
        $relet_info = $builder->first();
        if( !$relet_info ){
            return false;
        }
        return new Relet( $relet_info );
    }
    /**
     * 获取商品
     * <p>当订单不存在时，抛出异常</p>
     * @param int   	$relet_no		    续租编号
     * @param int		$lock		锁
     * @return \App\Order\Modules\Repository\Relet\Relet
     * @return  bool
     */
    public static function getByReletNo( $relet_no, int $lock=0 ) {
        $builder = OrderRelet::where([
            ['relet_no', '=', $relet_no],
        ])->limit(1);
        if( $lock ){
            $builder->lockForUpdate();
        }
        $relet_info = $builder->first();
        if( !$relet_info ){
            return false;
        }

        return new Relet( $relet_info );
    }

}