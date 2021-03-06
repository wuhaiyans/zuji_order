<?php
namespace App\Order\Modules\Repository;
use App\Lib\Common\LogApi;
use App\Order\Models\OrderOverdueVisit;
use App\Order\Modules\Repository\ShortMessage\DestineCreate;
use Illuminate\Support\Facades\DB;
use App\Order\Models\OrderOverdueDeduction;

class OrderOverdueVisitRepository
{
    /**
     *  获取最新回访记录
     * @author qinliping
     * @param  array $where  搜索条件
     */
    public static function getOverdueVisitinfo($where)
    {
        $visitDetail = OrderOverdueVisit::where($where)->orderBy('create_time',"DESC")->get()->toArray();//获取订单的所有回访信息
        if(!$visitDetail){
            return [];
        }
        return $visitDetail;
    }
    /*
      创建回访记录
     * @author qinliping
     * @param  array $params
    * [
     *   'order_no'  =>'', //订单编号  【必选】   string
     *   'visit_id'  =>'', //回访id    【必选】   int
     *   'visit_text'=>''  //回访备注  【必选】   string
     * ]
     * @return bool
     */
    public static function createVisit($params){
        $data = [
            'order_no'=>$params['order_no'],
            'visit_id'=>$params['visit_id'],
            'visit_text'=>$params['visit_text'],
            'create_time'=>time()
        ];
        $createResult = OrderOverdueVisit::query()->insertGetId($data);//插入回访记录
        if( !$createResult ){
            return false;
        }
        //修改订单的记录的回访id
        $updateResult = OrderOverdueDeduction::where('order_no','=',$params['order_no'])->update(['visit_id'=>$createResult]);
        if( !$updateResult ){
            return false;
        }

        return true;
    }
}