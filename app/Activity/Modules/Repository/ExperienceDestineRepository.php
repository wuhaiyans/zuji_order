<?php
namespace App\Activity\Modules\Repository;

use App\Activity\Models\ActivityDestine;
use App\Activity\Models\ActivityExperienceDestine;
use App\Activity\Modules\Inc\DestineStatus;

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
     *      'activity_id'   => ' ', //【必须】 int   活动ID
     *      'user_id'       => ' ', //【必须】 int   用户ID
     *      'mobile'        => ' ', //【必须】 string 用户手机号
     *      'destine_amount'=> ' ', //【必须】 float  预定金额
     *      'pay_type'      => ' ', //【必须】 int  支付类型
     *      'app_id'        => ' ', //【必须】 int app_id
     *      'channel_id'    => ' ', //【必须】 int 渠道Id
     *      'activity_name' => ' ', //【必须】 string 活动名称
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
            'experience_id' => 'required',
        ]);
        if(count($data)<9){
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

        $this->experienceDestine->destine_status = DestineStatus::DestineCreated;
        $this->experienceDestine->create_time = time();
        $this->experienceDestine->update_time = time();

        return $this->experienceDestine->save();
    }

    /**
     * 查询当前用户是否已经预约活动
     * @param $user_id   用户ID
     * @param $activity_id 总活动ID
     * @return array
     */

    public static function unActivityDestineByUser($userId,$activityId){
        if (empty($userId)) return false;
        if (empty($activityId)) return false;
        $info = ActivityDestine::query()->where([
            ['user_id', '=', $userId],
            ['activity_id', '=', $activityId],
        ])->first();
        return $info;
    }



}