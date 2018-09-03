<?php
namespace App\Activity\Modules\Repository;

use App\Activity\Models\ActivityDestine;
use App\Activity\Modules\Inc\DestineStatus;

class ActivityDestineRepository
{

    protected $activityDestine;


    public function __construct()
    {
        $this->activityDestine = new ActivityDestine();
    }

    /**
     * 创建活动预定
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
            'pay_type'      => 'required',
            'app_id'        => 'required',
            'channel_id'    => 'required',
            'activity_name' => 'required',
        ]);
        if(count($data)<9){
            return false;
        }
        $this->activityDestine->destine_no = $data['destine_no'];
        $this->activityDestine->activity_id = $data['activity_id'];
        $this->activityDestine->user_id = $data['user_id'];
        $this->activityDestine->mobile = $data['mobile'];
        $this->activityDestine->destine_amount = $data['destine_amount'];
        $this->activityDestine->pay_type = $data['pay_type'];
        $this->activityDestine->app_id = $data['app_id'];
        $this->activityDestine->channel_id = $data['channel_id'];
        $this->activityDestine->activity_name = $data['activity_name'];
        $this->activityDestine->destine_status = DestineStatus::DestineCreated;
        $this->activityDestine->create_time = time();
        $this->activityDestine->update_time = time();

        return $this->activityDestine->save();
    }

    /**
     * 查询当前用户是否已经预约活动
     * @param $user_id   用户ID
     * @param $activity_id 活动ID
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