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
     * @param $whereInArray渠道类型
     * @return array
     *
     */
    public static function get_list(array $where,$whereInArray,array $additional){
        $parcels = DB::table('order_return')
            ->leftJoin('order_info','order_return.order_no', '=', 'order_info.order_no')
            ->leftJoin('order_goods',[['order_return.order_no', '=', 'order_goods.order_no'],['order_return.goods_no', '=', 'order_goods.goods_no']])
            ->when(!empty($whereInArray),function($join) use ($whereInArray) {
                return $join->whereIn('order_info.channel_id', $whereInArray);
            })
            ->when(!empty($where),function($join) use ($where) {
                return $join->where($where);
            })
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
     * [
     * 'visit_id'    => '',  【可选】  回访id    int
     * 'keywords'    =>'',   【可选】  关键字    string
     * 'kw_type'     =>'',   【可选】  查询类型  string
     * 'zuqi_type'   =>'',   【可选】  租期类型  int
     *  'overDue_period'=>'', 【可选】 逾期时间段
     * 'page'        =>'',   【可选】  页数       int
     * 'size'        =>''    【可选】  条数       int
     * ]
     * 'channel_id' => ''     【必传】渠道
     */
    public static function getAdminOrderList($param = array(),$channel_id, $pagesize=5)
    {
        $whereArray = array();
        $whereInArray = array();
        //根据手机号
        if (isset($param['kw_type']) && $param['kw_type']=='mobile' && !empty($param['keywords']))
        {
            $whereArray[] = ['order_info.mobile', '=', $param['keywords']];
        }
        //根据订单号
        if (isset($param['kw_type']) && $param['kw_type']=='order_no' && !empty($param['keywords']))
        {
            $whereArray[] = ['order_info.order_no', '=', $param['keywords']];
        }

        //回访标识
        if (isset($param['visit_id'])) {
            $whereArray[] = ['order_info_visit.visit_id', '=', $param['visit_id']];
        }
        //租期类型
        if (isset($param['zuqi_type'])) {
            $whereArray[] = ['order_info.zuqi_type', '=', $param['zuqi_type']];
        }

        //逾期时间段
        if(isset($param['overDue_period'])){
            if($param['overDue_period'] == "m1"){
                LogApi::debug("[overDue]选择逾期时间段".$param['overDue_period']);
                $start = time()-30*3600*24;
                $end = time();
            }
            if($param['overDue_period'] == "m2"){
                $start = time()-60*3600*24;
                $end = time()-30*3600*24;
            }
            if($param['overDue_period'] == "m3"){
                $start = time()-90*3600*24;
                $end = time()-60*3600*24;

            }
            if($param['overDue_period'] == "m4"){
                $start = time()-120*3600*24;
                $end = time()-90*3600*24;
            }
            if($param['overDue_period'] == "m5"){
                $start = time()-150*3600*24;
                $end = time()-120*3600*24;

            }
            if($param['overDue_period'] == "m6"){
                $start = time()-180*3600*24;
                $end = time()-150*3600*24;
            }
            $whereArray[] =[ 'order_goods.end_time', '<=',$end];
            $whereArray[] =[ 'order_goods.end_time', '>=',$start];
        }

        if (isset($param['size'])) {
            $pagesize = $param['size'];
        }

        if (isset($param['page'])) {
            $page = $param['page'];
        } else {

            $page = 1;
        }
        //第三方渠道类型
        if (isset($channel_id) && !empty($channel_id)) {

            $whereInArray = $channel_id;
        }

        $whereArray[] = ['order_goods.end_time', '>', 0];
        $whereArray[] = ['order_goods.end_time','<=',time()];
        $whereArray[] = ['order_info.order_status','=',OrderStatus::OrderInService];  //租用中

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
            ->when(!empty($whereInArray),function($join) use ($whereInArray) {
                return $join->whereIn('order_info.channel_id', $whereInArray);
            })
            ->when(!empty($whereArray),function($join) use ($whereArray) {
                return $join->where($whereArray);
            })
          //  ->where($whereArray)
            ->first();


        $count = objectToArray($count)['order_count'];
        LogApi::debug("【overDue】数据计数",$count);
        if (!isset($param['count'])) {

//        sql_profiler();
            $orderList = DB::table('order_info')
                ->select('order_info.order_no','order_goods.end_time','order_info.create_time')
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
                ->when(!empty($whereInArray),function($join) use ($whereInArray) {
                    return $join->whereIn('order_info.channel_id', $whereInArray);
                })
                ->when(!empty($whereArray),function($join) use ($whereArray) {
                    return $join->where($whereArray);
                })
               // ->where($whereArray)
                ->orderBy('order_goods.end_time', 'ASC')
                ->skip(($page - 1) * $pagesize)->take($pagesize)
                ->get();

            $orderArray = objectToArray($orderList);
            LogApi::debug("【overDue】获取搜索后的数组",$orderArray);
            if ($orderArray) {
                $orderIds = array_column($orderArray,"order_no");

                $orderList =  DB::table('order_info as o')
                    ->select('o.order_no','o.order_amount','o.order_yajin','o.order_insurance','o.create_time','o.order_status','o.freeze_type','o.appid','o.pay_type','o.zuqi_type','o.user_id','o.mobile','o.predict_delivery_time','d.address_info','d.name','d.consignee_mobile','v.visit_id','v.visit_text','v.id','l.logistics_no','c.matching','g.end_time','g.goods_status','v.visit_text')
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
                LogApi::debug("【overDue】获取搜索后的数组",objectToArray($orderList));
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

    /*线下退货退款列表
     * ->paginate: 参数
     *  perPage:表示每页显示的条目数量
       columns:接收数组，可以向数组里传输字段，可以添加多个字段用来查询显示每一个条目的结果
       pageName:表示在返回链接的时候的参数的前缀名称，在使用控制器模式接收参数的时候会用到
       page:表示查询第几页及查询页码
     *@params
     * [
     *   'begin_time' => '', //开始时间  int     【可选】
     *   'end_time'   =>'',  //结束时间  int     【可选】
     *   'kw_type'   =>'',   //搜索条件  string  【可选】
     *   'keyword'   =>'',   //关键词    string  【可选】
     *   'page'      =>'',   //页数       int    【可选】
     *   'size'      =>'',   //条数       int    【可选】
     * ]
     * 'channel_id'   =>''   //渠道       【必传】
     *@return array
     *
     */
    public static function underLineReturn($param = array(),$channel_id, $pagesize=5){
        $whereArray = array();
        $whereInArray = array();
        //根据手机号
        if (isset($param['kw_type']) && $param['kw_type']=='mobile' && !empty($param['keywords']))
        {
            $whereArray[] = ['order_info.mobile', '=', $param['keywords']];
        }
        //根据订单号
        if (isset($param['kw_type']) && $param['kw_type']=='name' && !empty($param['keywords']))
        {
            $whereArray[] = ['order_user_address.name', '=', $param['keywords']];
        }
        //创建时间
        if (isset($param['begin_time']) && !empty($param['begin_time']) && (!isset($param['end_time']) || empty($param['end_time']))) {
            $whereArray[] = ['order_return.create_time', '>=', strtotime($param['begin_time'])];
        }

        //创建时间
        if (isset($param['begin_time']) && !empty($param['begin_time']) && isset($param['end_time']) && !empty($param['end_time'])) {
            $whereArray[] = ['order_return.create_time', '>=', strtotime($param['begin_time'])];
            $whereArray[] = ['order_return.create_time', '<', (strtotime($param['end_time'])+3600*24)];
        }

        if (isset($param['size'])) {
            $pagesize = $param['size'];
        }

        if (isset($param['page'])) {
            $page = $param['page'];
        } else {

            $page = 1;
        }

        $whereArray[] = ['order_return.business_type','=',ReturnStatus::UnderLineBusiness];  //线下业务
        $whereArray[] = ['order_return.business_key','=',OrderStatus::BUSINESS_RETURN];      //退货业务
        //第三方渠道类型
        if (isset($channel_id) && !empty($channel_id)) {

            $whereInArray = $channel_id;
        }

        LogApi::debug("【underLineReturn】搜索条件",$whereArray);

        $count = DB::table('order_return')
            ->select(DB::raw('count(order_return.order_no) as order_count'))
            ->join('order_info',function($join){
                $join->on('order_return.order_no', '=', 'order_info.order_no');
            }, null,null,'inner')
            ->join('order_user_address',function($join){
                $join->on('order_return.order_no', '=', 'order_user_address.order_no');
            }, null,null,'inner')
            ->join('order_goods',function($join){
                $join->on('order_return.order_no', '=', 'order_goods.order_no');
            }, null,null,'left')
            ->when(!empty($whereInArray),function($join) use ($whereInArray) {
                return $join->whereIn('order_info.channel_id', $whereInArray);
            })
            ->when(!empty($whereArray),function($join) use ($whereArray) {
                return $join->where($whereArray);
            })
          //  ->where($whereArray)
            ->first();


        $count = objectToArray($count)['order_count'];
        LogApi::debug("【underLineReturn】数据计数",$count);
        if (!isset($param['count'])) {

//        sql_profiler();
            $orderList = DB::table('order_return')
                ->select('order_return.create_time','order_return.order_no')
                ->join('order_info',function($join){
                    $join->on('order_return.order_no', '=', 'order_info.order_no');
                }, null,null,'left')
                ->join('order_user_address',function($join){
                    $join->on('order_return.order_no', '=', 'order_user_address.order_no');
                }, null,null,'inner')
                ->join('order_goods',function($join){
                    $join->on('order_return.order_no', '=', 'order_goods.order_no');
                }, null,null,'left')
                ->where($whereArray)
                ->orderBy('order_return.create_time', 'DESC')
                ->skip(($page - 1) * $pagesize)->take($pagesize)
                ->get();

            $orderArray = objectToArray($orderList);
            LogApi::debug("【underLineReturn】获取搜索后的数组",$orderArray);
            if ($orderArray) {
                $orderIds = array_column($orderArray,"order_no");

                $orderList =  DB::table('order_return as r')
                    ->select('r.order_no','r.auth_deduction_amount','r.remark','r.status','r.create_time','g.goods_name','g.zuji_goods_id','o.mobile','d.name')
                    ->whereIn('r.order_no', $orderIds)
                    ->join('order_info as o',function($join){
                        $join->on('r.order_no', '=', 'o.order_no');
                    }, null,null,'inner')
                    ->join('order_user_address as d',function($join){
                        $join->on('r.order_no', '=', 'd.order_no');
                    }, null,null,'inner')
                    ->join('order_goods as g',function($join){
                        $join->on('r.order_no', '=', 'g.order_no');
                    }, null,null,'left')
                    ->orderBy('r.create_time', 'DESC')
                    ->get();
                LogApi::debug("【underLineReturn】获取搜索后的数组",objectToArray($orderList));
                $orderArrays['data'] = array_column(objectToArray($orderList),NULL,'order_no');;
             //   $orderArrays['orderIds'] = $orderIds;
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