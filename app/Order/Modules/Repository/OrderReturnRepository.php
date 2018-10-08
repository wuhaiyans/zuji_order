<?php
namespace App\Order\Modules\Repository;
use App\Lib\Common\LogApi;
use App\Order\Models\OrderReturn;
use App\Order\Models\OrderGoods;
use App\Order\Models\OrderGoodsExtend;
use App\Order\Models\Order;
use App\Order\Models\OrderUserInfo;
use App\Order\Models\OrderPayModel;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Inc\ReturnStatus;
use App\Order\Modules\Inc\OrderGoodStatus;
use Illuminate\Support\Facades\DB;

class OrderReturnRepository
{
    private $orderReturn;
    public function __construct(orderReturn $orderReturn)
    {
        $this->orderReturn = $orderReturn;
    }

    /**
     * 获取退货单信息,根据退货单id
     * @param $params
     *
     */
    public static function getReturnInfo($id){
        $where[]=['id','=',$id];
        $res=orderReturn::where($where)->first();
        if(!$res){
           return false;
        }
        return $res->toArray();
    }

    /**
     * 创建退换货单
     * @param array $data
     * [
     *   'goods_no'      => '', string  商品编号
     *   'order_no'      => '', string  订单编号
     *   'business_key'  => '', int     业务类型
     *   'reason_id'     => '', int     退换货id
     *   'reason_text'   => '', string  退货说明
     *   'user_id'       => '', int     用户id
     *   'status'        => '', int     退换货单状态
     *   'refund_no'     => '', string  退换货单号
     *   'pay_amount'    =>'' ,         实付金额
     *   'auth_unfreeze_amount'  => '', 应退押金
     *   'refund_amount'  => '' ,       应退金额
     *   'create_time'   => '', int    创建时间
     *
     *
     * ]
     * @return  bool
     *
     */
   public static function createReturn(array $data){
       $createReturn=orderReturn::query()->insert($data);
       if(!$createReturn){
           return false;
       }
       return true;
   }

    /**
     * 创建退款单
     * @param array $data
     * [
     *   'order_no'      => '', string  订单编号
     *   'business_key'  => '', int     业务类型
     *   'user_id'       => '', int     用户id
     *   'status'        => '', int     退换货单状态
     *   'refund_no'     => '', string  退款单号
     *   'pay_amount'    =>'' ,         实付金额
     *   'auth_unfreeze_amount'  => '', 应退押金
     *   'refund_amount'  => '' ,       应退金额
     *   'create_time'   => '', int    创建时间
     * ]
     * @return bool
     */
   public static function createRefund(array $data){
       $createRefund=orderReturn::query()->insert($data);
       if(!$createRefund){
           return false;
       }
       return true;
   }

    /**
     * 查询退货、退款列表
     * @param $where
     * @param $additional
     * @return array
     *
     */
    public static function get_list(array $where,array $additional){
        $parcels = DB::table('order_return')
            ->leftJoin('order_info','order_return.order_no', '=', 'order_info.order_no')
            ->leftJoin('order_goods',[['order_return.order_no', '=', 'order_goods.order_no'],['order_return.goods_no', '=', 'order_goods.goods_no']])
            ->where($where)
            ->select('order_return.create_time as c_time','order_return.*','order_info.create_time','order_info.mobile','order_info.order_amount','order_info.order_status','order_goods.goods_name','order_goods.zuqi')
            ->orderBy('order_return.create_time', 'DESC')
            ->paginate($additional['size'],$columns = ['*'], $pageName = 'page', $additional['page']);
        if($parcels){
            return $parcels->toArray();
        }
        return [];
    }
    /**
     * 导出 查询退货、退款列表
     * @param $where
     * @param $additional
     * @return array
     *
     */
    public static function getReturnList(array $where){
        $parcels = DB::table('order_return')
            ->leftJoin('order_info','order_return.order_no', '=', 'order_info.order_no')
            ->leftJoin('order_goods',[['order_return.order_no', '=', 'order_goods.order_no'],['order_return.goods_no', '=', 'order_goods.goods_no']])
            ->where($where)
            ->select('order_return.create_time as c_time','order_return.*','order_info.*','order_goods.goods_name','order_goods.zuqi')
            ->orderBy('order_return.create_time', 'DESC')
            ->get();
        if($parcels){
            return $parcels->toArray();
        }
        return [];
    }
    /**
     * 获取订单支付编号信息
     * @param $business_type
     * @param $business_no
     */
    public static function  getPayNo(int $business_type,string $business_no){
        $Data=OrderPayModel::where([['business_type','=',$business_type],['business_no','=',$business_no]])->first();
        if(!$Data){
            return false;
        }
        return $Data->toArray();
    }
    /**
     * 获取退换货单数据
     * @param $where  条件参数
     **[
     *   'order_no'    =>'' ,订单编号   string   【必传】
     *   'business_key'=>'',业务类型    int      【必传】
     *   'status'      =>'',退货单状态  int
     *   'evaluation_status' =>'', 检测状态   int    注：退货单状态和检测状态二传一即可
     * ]
     * @return array
     */
    public static function returnApplyList(array $where){
        $return_result= DB::table('order_return')
            ->leftJoin('order_goods', [['order_return.order_no', '=', 'order_goods.order_no'],['order_return.goods_no', '=', 'order_goods.goods_no']])
            ->where($where)
            ->select('order_goods.*','order_return.*')
            ->get()->toArray();
        if(!$return_result){
            return [];
        }
        return $return_result;
    }

    /**
     * 获取订单的商品信息
     * @param $order_no
     * @return bool|\Illuminate\Support\Collection
     */
    public static function getGoodsInfo(string $order_no){
        $where[]=['order_no','=',$order_no];
        $getGoods=OrderGoods::where($where)->get();
        if(!$getGoods){
            return false;
        }
        return $getGoods;
    }

    /**
     * 获取退货待审核的数量
     * @param $where
     */
    public static function returnCheckingCount(){
        $where[]=["status","=",ReturnStatus::ReturnCreated];
        $where[]=["business_key","=",OrderStatus::BUSINESS_RETURN];
        $getReturn=orderReturn::where($where)->count();
        return $getReturn;
    }

    /**
     * 获取已取消除外的退货单信息
     * @param $order_no
     * @param $gods_no
     */
    public static function returnList(string $order_no,string $goods_no){
        $where[]=['goods_no','=',$goods_no];
        $where[]=['order_no','=',$order_no];
        $where[]=['status','!=',ReturnStatus::ReturnCanceled];  //状态不为已取消
        $getReturn=orderReturn::where($where)->first();
        if(!$getReturn){
            return false;
        }
        return $getReturn->toArray($getReturn);

    }

    /**
     *  获取后台用户逾期列表
     * ->paginate: 参数
     *  perPage:表示每页显示的条目数量
       columns:接收数组，可以向数组里传输字段，可以添加多个字段用来查询显示每一个条目的结果
       pageName:表示在返回链接的时候的参数的前缀名称，在使用控制器模式接收参数的时候会用到
       page:表示查询第几页及查询页码
     * @param array $param  获取订单列表参数
     */
    public static function getAdminOrderList($param = array(), $pagesize=5)
    {
        $whereArray = array();
        $orWhereArray = array();

        //根据手机号
        if (isset($param['kw_type']) && $param['kw_type']=='mobile' && !empty($param['keywords']))
        {
            $orWhereArray[] = ['order_info.mobile', '=', $param['keywords'],'or'];
            $orWhereArray[] = ['order_user_address.consignee_mobile', '=', $param['keywords'],'or'];
        }
        //根据订单号
        elseif (isset($param['kw_type']) && $param['kw_type']=='order_no' && !empty($param['keywords']))
        {
            $whereArray[] = ['order_info.order_no', '=', $param['keywords']];
        }

        //回访标识
        if (isset($param['visit_id'])) {
            $whereArray[] = ['order_info_visit.visit_id', '=', $param['visit_id']];
        }

        if (isset($param['size'])) {
            $pagesize = $param['size'];
        }

        if (isset($param['page'])) {
            $page = $param['page'];
        } else {

            $page = 1;
        }

        $whereArray[] = ['order_info.create_time', '>', 0];
        $whereArray[] = ['order_goods.end_time','<=',time()];
        $whereArray[] = ['order_goods.goods_status','=',OrderGoodStatus::RENTING_MACHINE];
        LogApi::debug("【overDue】搜索条件",$whereArray);
        $count = DB::table('order_info')
            ->select(DB::raw('count(order_info.order_no) as order_count'))
            ->join('order_user_address',function($join){
                $join->on('order_info.order_no', '=', 'order_user_address.order_no');
            }, null,null,'inner')
            ->join('order_info_visit',function($join){
                $join->on('order_info.order_no', '=', 'order_info_visit.order_no');
            }, null,null,'left')
            ->join('order_goods',function($join){
                $join->on('order_info.order_no', '=', 'order_goods.order_no');
            }, null,null,'left')
            ->join('order_delivery',function($join){
                $join->on('order_info.order_no', '=', 'order_delivery.order_no');
            }, null,null,'left')
            ->where($whereArray)
            ->where($orWhereArray)
            ->first();


        $count = objectToArray($count)['order_count'];
        LogApi::debug("【overDue】数据计数",$count);
        if (!isset($param['count'])) {

//        sql_profiler();
            $orderList = DB::table('order_info')
                ->select('order_info.order_no,order_goods.end_time')
                ->join('order_user_address',function($join){
                    $join->on('order_info.order_no', '=', 'order_user_address.order_no');
                }, null,null,'inner')
                ->join('order_info_visit',function($join){
                    $join->on('order_info.order_no', '=', 'order_info_visit.order_no');
                }, null,null,'left')
                ->join('order_goods',function($join){
                    $join->on('order_info.order_no', '=', 'order_goods.order_no');
                }, null,null,'left')
                ->join('order_delivery',function($join){
                    $join->on('order_info.order_no', '=', 'order_delivery.order_no');
                }, null,null,'left')
                ->where($whereArray)
                ->where($orWhereArray)
                ->orderBy('order_goods.end_time', 'ASC')
//            ->paginate($pagesize,$columns = ['order_info.order_no'], 'page', $param['page']);
//            ->forPage($page, $pagesize)
//
                ->skip(($page - 1) * $pagesize)->take($pagesize)
                ->get();

            $orderArray = objectToArray($orderList);
            LogApi::debug("【overDue】获取搜索后的数组",$orderArray);
            if ($orderArray) {
                $orderIds = array_column($orderArray,"order_no");
//           dd($orderIds);
//            sql_profiler();
                $orderList =  DB::table('order_info as o')
                    ->select('o.order_no','o.order_amount','o.order_yajin','o.order_insurance','o.create_time','o.order_status','o.freeze_type','o.appid','o.pay_type','o.zuqi_type','o.user_id','o.mobile','o.predict_delivery_time','d.address_info','d.name','d.consignee_mobile','v.visit_id','v.visit_text','v.id','l.logistics_no','c.matching','g.end_time')
                    ->whereIn('o.order_no', $orderIds)
                    ->join('order_user_address as d',function($join){
                        $join->on('o.order_no', '=', 'd.order_no');
                    }, null,null,'inner')
                    ->join('order_info_visit as v',function($join){
                        $join->on('o.order_no', '=', 'v.order_no');
                    }, null,null,'left')
                    ->join('order_delivery as l',function($join){
                        $join->on('o.order_no', '=', 'l.order_no');
                    }, null,null,'left')
                    ->join('order_goods as g',function($join){
                        $join->on('o.order_no', '=', 'g.order_no');
                    }, null,null,'left')
                    ->join('order_user_certified as c',function($join){
                        $join->on('o.order_no', '=', 'c.order_no');
                    }, null,null,'left')
                    ->orderBy('g.end_time', 'ASC')
                    ->get();

                $orderArrays['data'] = array_column(objectToArray($orderList),NULL,'order_no');;
                $orderArrays['orderIds'] = $orderIds;
                $orderArrays['total'] = $count;
                $orderArrays['last_page'] = ceil($count/$pagesize);

            } else {

                return false;
            }




//            leftJoin('order_user_address', 'order_info.order_no', '=', 'order_user_address.order_no')

        }else {

            $orderArrays['total'] = $count;

        }
        return $orderArrays;

    }



}