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
use App\Activity\Modules\Repository\ActiveInviteRepository;
use App\Activity\Modules\Repository\ActivityThemeRepository;
use App\Activity\Modules\Repository\ExperienceDestineRepository;
use App\Lib\Goods\Goods;
use App\Lib\Risk\Risk;
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
        $page = $page>0?$page-1:$page;
        $page = $page>=$sum?$sum:$page-1;
        $limit = $limit<50?$limit:20;
        $offset = $page*$limit;

        $list = ActivityAppointment::query()->where($where)->offset($offset)->limit($limit)->get()->toArray();
        $data = [
            'count' => $count,
            'total_page' =>$sum,
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
        //获取新苹果预约数据
        $count = ActivityDestine::query()->where($where)->count();

        $sum = ceil($count/$limit);
        $page = $page>0?$page-1:$page;
        $page = $page>=$sum?$sum:$page;
        $limit = $limit<50?$limit:20;
        $offset = $page*$limit;
        $list = ActivityDestine::query()->where($where)->offset($offset)->limit($limit)->get()->toArray();
        if($list){
            //拆分活动id
            $advanceIds = array_column($list,"activity_id");
            array_unique($advanceIds);
            //获取预约活动
            $activityList = ActivityAppointment::query()->whereIn("id",$advanceIds)->get()->toArray();
            $activityList = array_column($activityList,null,"id");
            //获取活动商品
            $goodsList = ActivityGoodsAppointment::query()->wherein("appointment_id",$advanceIds)->get()->toArray();
            $goodsList = array_keys_arrange($goodsList,"appointment_id");
            //拼装数据格式
            foreach($list as &$item){
                //下单按钮
                $order_btn = false;
                if(!empty($goodsList[$item['activity_id']]['spu_id'])){
                    $order_btn = true;
                }
                $item['destine_amount'] = sprintf('%.2f',$item['destine_amount']);
                $item['order_btn'] = $order_btn;
                $item['destine_status'] = DestineStatus::getStatusName($item['destine_status']);
                $item['title'] = $activityList[$item['activity_id']]['title'];
                $item['appointment_image'] = $activityList[$item['activity_id']]['appointment_image'];
                $item['type'] = 1;
            }
        }

        //获预约活动信息
        $activityInfo = ExperienceDestineRepository::getUserExperience($userInfo['uid'],1);

        if($activityInfo){
            //默认渠道
            $activityInfo['app_id'] = 139;
            //获取邀请人数
            $count = ActiveInviteRepository::getCount(['uid'=>$userInfo['uid'],'activity_id'=>1]);
            $count = $count?$count:0;
            $activityInfo['zuqi_day'] = $count;
            $activityInfo['zuqi'] -= $count;
            $activityInfo['type'] = 2;
            $activityInfo['content'] = '尊敬的客户您好，请您到店领取商品。 地址为：天津市西青区师范大学南门华木里底商711便利店直走100米——拿趣用数码共享便利店。 客服电话：18611002204';
            $activityInfo['destine_name'] = DestineStatus::getStatusName($activityInfo['destine_status']);
            //把一元活动数据追加到苹果预约数据后面

            $yaoqin_btn = false;
            $renzheng_btn = false;
            $lingqu_btn = false;

            //按钮状态实现
            if($activityInfo['destine_status'] == DestineStatus::DestinePayed){
                //已支付显示邀请
                $yaoqin_btn = true;
                //获取活动主题信息 门店开业显示前往认证
                $themeInfo = ActivityThemeRepository::getInfo(['activity_id'=>1]);
                if(time()>=$themeInfo['opening_time']){
                    $switch = env("ACTIVITY_FENGKONG");
                    //获取认证信息 通过认证显示领取
                    if($switch){
                        $risk = new Risk();
                        $riskInfo = $risk->getKnight(['user_id'=>$userInfo['uid']]);
                        if(isset($riskInfo['is_chsi']) && $riskInfo['is_chsi']==1){
                            $lingqu_btn = true;
                        }
                        else{
                            $renzheng_btn =true;
                        }
                    }else{
                        $lingqu_btn = true;
                    }

                }
            };
            $activityInfo['yaoqin_btn'] = $yaoqin_btn;
            $activityInfo['renzheng_btn'] = $renzheng_btn;
            $activityInfo['lingqu_btn'] = $lingqu_btn;
            //获取商品信息
            $spuInfo = Goods::getSpuInfo($activityInfo['spu_id']);
            if($spuInfo){
                $activityInfo['goods_images'] = $spuInfo["spu_info"]['thumb'];
            }
            $list[] = $activityInfo;
        }

        $data = [
            'count' => $count,
            'total_page' =>$sum,
            'data' =>$list
        ];
        return apiResponse($data,ApiStatus::CODE_0);
    }
}