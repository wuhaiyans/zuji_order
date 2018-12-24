<?php
namespace App\Activity\Modules\Repository;

use App\Activity\Models\ActivityDestine;
use App\Activity\Models\ActivityExperience;
use App\Activity\Models\ActivityExperienceDestine;
use App\Activity\Modules\Inc\DestineStatus;
use Illuminate\Support\Facades\DB;

class ExperienceDestineRepository
{

    protected $experienceDestine;


    public function __construct()
    {
        $this->experienceDestine = new ActivityExperienceDestine();
    }

    /**
     * 增加新的活动体验
     * @param $data
     *  [
     *      'destine_no'    => ' ', //【必须】 string 预定编号
     *      'activity_id'   => ' ', //【必须】 int   总活动ID
     *      'experience_id' => ' ', //【必须】 int   活动ID
     *      'user_id'       => ' ', //【必须】 int   用户ID
     *      'mobile'        => ' ', //【必须】 string 用户手机号
     *      'destine_amount'=> ' ', //【必须】 float  预定金额
     *      'pay_type'      => ' ', //【必须】 int  支付类型
     *      'app_id'        => ' ', //【必须】 int app_id
     *      'channel_id'    => ' ', //【必须】 int 渠道Id
     *      'pay_channel'   => ' ', //【必须】 string 支付渠道
     *      'zuqi'          => ' ', //【必须】 int 租期
    ]
     * @return bool
     */

    public function add($data){
        $data = filter_array($data, [
            'destine_no'    => 'required',
            'activity_id'   => 'required',
            'user_id'       => 'required',
            'mobile'        => 'required',
            'destine_amount'=> 'required',
            'zuqi'          => 'required',
            'app_id'        => 'required',
            'pay_channel'   => 'required',
            'channel_id'    => 'required',
            'pay_type'      => 'required',
            'experience_id' => 'required',
            'open_id'       => 'required',
            'goods_name'    => 'required',
        ]);
        if(count($data)<13){
            return false;
        }
        $this->experienceDestine->destine_no = $data['destine_no'];
        $this->experienceDestine->activity_id = $data['activity_id'];
        $this->experienceDestine->user_id = $data['user_id'];
        $this->experienceDestine->mobile = $data['mobile'];
        $this->experienceDestine->destine_amount = $data['destine_amount'];
        $this->experienceDestine->zuqi = $data['zuqi'];
        $this->experienceDestine->app_id = $data['app_id'];
        $this->experienceDestine->pay_channel = $data['pay_channel'];
        $this->experienceDestine->experience_id = $data['experience_id'];
        $this->experienceDestine->pay_type = $data['pay_type'];
        $this->experienceDestine->channel_id = $data['channel_id'];
        $this->experienceDestine->open_id = $data['open_id'];
        $this->experienceDestine->goods_name = $data['goods_name'];

        $this->experienceDestine->destine_status = DestineStatus::DestineCreated;
        $this->experienceDestine->create_time = time();
        $this->experienceDestine->update_time = time();

        return $this->experienceDestine->save();
    }

    /**
     * 查询当前用户是否已经预约活动
     * @param $userId   用户ID
     * @param $activityId 总活动ID
     * @return array
     */

    public static function unActivityDestineByUser($userId,$activityId){
        if (empty($userId)) return false;
        if (empty($activityId)) return false;
        $info = ActivityExperienceDestine::query()->where([
            ['user_id', '=', $userId],
            ['activity_id', '=', $activityId],
        ])->orderBy('id','DESC')->first();
        return $info;
    }
    /**
     * 查询当前用户是否已经预约活动
     * @param $userId   用户ID
     * @param $experienceId 总活动ID
     * @return array
     */

    public static function unDestineByUserAndExperience($userId,$experienceId){
        if (empty($userId)) return false;
        if (empty($activityId)) return false;
        $info = ActivityExperienceDestine::query()->where([
            ['user_id', '=', $userId],
            ['experience_id', '=', $experienceId],
        ])->first();
        return $info;
    }

    /**
     * 查询当前参加活动 和活动详情
     * @param $userId   用户ID
     * @param $experienceId 总活动ID
     * @return array
     */

    public static function getUserExperience($userId,$experienceId){
        if (empty($userId)) return false;
        if (empty($experienceId)) return false;
        $where = [
            ['user_id','=',$userId],
            ['activity_id','=',$experienceId],
            ['destine_status','>',DestineStatus::DestineCreated]
        ];
        $info = ActivityExperienceDestine::query()->where($where)->first();
        if($info){
            $info = $info->toArray();
            $experience = ActivityExperience::query()->where(['id'=>$info['experience_id']])->first()->toArray();;
            $info = array_merge($experience,$info);
        }
        return !empty($info)?objectToArray($info):false;
    }
    /**
     * 获取预定活动分页列表
     * @param $params
     * [
     *   'mobile'       =>'',   //【可选】 string 手机号
     *   'page'         =>'',   //【可选】 int 页数
     *   'size'         =>''    //【可选】 int 每页数量
     * ]
     *

     */
    public static  function getDestinePageList($param=array()){
        $page = empty($param['page']) || !isset($param['page']) ? 1 : $param['page'];
        $size = !empty($param['size']) && isset($param['size']) ? $param['size'] : config('web.pre_page_size');
        $whereArray= self::get_where($param);  //获取搜索的条件
        $destineList =  DB::table('order_activity_experience_destine')
            ->select('order_activity_experience_destine.*')
            ->where($whereArray)
            ->orderBy('pay_time', 'DESC')
            ->paginate($size,$columns = ['*'], $pageName = 'page', $page);


        if($destineList){
            return $destineList->toArray();
        }
        return [];
    }
    /**
     * 列表条件
     */
    public static function get_where($param=array()){
        $whereArray=[];
        $whereArray[] = ['destine_status', '>=', DestineStatus::DestinePayed];
        //根据用户手机号
        if (isset($param['mobile']) && !empty($param['mobile'])) {

            $whereArray[] = ['mobile', '=', $param['mobile']];
        }
        //根据领取状态
        if (isset($param['destine_status']) && !empty($param['destine_status'])) {

            $whereArray[] = ['destine_status', '=', $param['destine_status']];
        }
        return $whereArray;
    }


}