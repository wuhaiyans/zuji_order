<?php
/**
 * 预约活动
 * @access public (访问修饰符)
 * @author limin <limin@huishoubao.com>
 * @copyright (c) 2018, Huishoubao
 */

namespace App\Activity\Controllers\Api\v1;

use App\Activity\Models\ActivityAppointment;
use App\Activity\Models\ActivityDestine;
use App\Activity\Models\ActivityGoodsAppointment;
use App\Activity\Modules\Inc\DestineStatus;
use Illuminate\Http\Request;
use App\Lib\ApiStatus;


class AdvanceActivityController extends Controller
{
    /*
     * 预约活动列表
     * @param null
     * @return json
     */
    public function getList(Request $request){
        $request = $request->all();
        $params = $request['params'];
        if(!$params['page']){
            $page = 0;
        }else{
            $page = $params['page'];
        }
        if(!$params['limit']){
            $limit = 20;
        }else{
            $limit = $params['limit'];
        }
        //设置查询条件
        $where= [
            ['begin_time',"<=",time()],
            ['end_time',">=",time()],
            ['appointment_status','=',0]
        ];
        //查询预约活动列表
        $count = ActivityAppointment::query()->where($where)->count();
        $sum = ceil($count/$limit);
        $page = $page>=$sum?$sum:$page;
        $limit = $limit<50?$limit:20;
        $offset = $page*$limit;

        $list = ActivityAppointment::query()->where($where)->offset($offset)->limit($limit)->get()->toArray();
        $data = [
            'count' => $count,
            'data' =>$list
        ];
        return apiResponse($data,ApiStatus::CODE_0);
    }
    /*
     * 预约活动详情
     * @param array $params 【必选】
     * [
     *      "id"=>"", 活动id
     * ]
     * @return json
     */
    public function get(Request $request){
        //获取请求参数
        $request = $request->all();
        $params = $request['params'];
        if(empty($params['id'])){
            return apiResponse([],ApiStatus::CODE_20001,"id必须");
        }
        $where = [
            ['id','=',$params['id']],
            ['begin_time',"<=",time()],
            ['end_time',">=",time()],
            ['appointment_status','=',0]
        ];
        //查询预约活动详情
        $data = ActivityAppointment::query()->where($where)->first();
        return apiResponse($data,ApiStatus::CODE_0);
    }

    /*
     * 我的预约
     * @param array $userinfo 【必选】
     * [
     *      "uid"=>"", 用户id
     * ]
     * @return json
     */
    public function myAdvance(Request $request){

        $request =$request->all();
        $params = $request['params'];
        if(!$params['page']){
            $page = 0;
        }else{
            $page = $params['page'];
        }
        if(!$params['limit']){
            $limit = 20;
        }else{
            $limit = $params['limit'];
        }
        $userInfo = $request['userinfo'];
        $where = [
            ['user_id','=',$userInfo['uid']],
            ['destine_status','<>',DestineStatus::DestineCreated]
        ];
        //查询我的预约列表
        $count = ActivityDestine::query()->where($where)->count();
        $sum = ceil($count/$limit);
        $page = $page>=$sum?$sum:$page;
        $limit = $limit<50?$limit:20;
        $offset = $page*$limit;
        $data = ActivityDestine::query()->where($where)->offset($offset)->limit($limit)->get()->toArray();
        if(!$data){
            return apiResponse($data,ApiStatus::CODE_0);
        }
        //拆分活动id
        $advanceIds = array_column($data,"activity_id");
        array_unique($advanceIds);
        //获取预约活动
        $activityList = ActivityAppointment::query()->whereIn("id",$advanceIds)->get()->toArray();
        $activityList = array_column($activityList,null,"id");
        //获取活动商品
        $goodsList = ActivityGoodsAppointment::query()->wherein("appointment_id",$advanceIds)->get()->toArray();
        $goodsList = array_keys_arrange($goodsList,"appointment_id");
        //拼装数据格式
        foreach($data as &$item){
            //下单按钮
            $order_btn = false;
            if(!empty($goodsList[$item['activity_id']]['spu_id'])){
                $order_btn = true;
            }
            $item['order_btn'] = $order_btn;
            $item['destine_status'] = DestineStatus::getStatusName($item['destine_status']);
            $item['title'] = $activityList[$item['activity_id']]['title'];
            $item['appointment_image'] = $activityList[$item['activity_id']]['appointment_image'];
        }
        return apiResponse($data,ApiStatus::CODE_0);
    }
}