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
     * @return bool
     */

    public function add($data){
        $data = filter_array($data, [
            'destine_no'    => 'required',
            'activity_id'   => 'required',
            'mobile'        => 'required',
            'destine_amount'=> 'required',
            'pay_type'      => 'required',
            'app_id'        => 'required',
            'channel_id'    => 'required',
            'trade_no'      => 'required',
        ]);
        if(count($data)<8){
            return false;
        }
        $this->activityDestine->destine_no = $data['destine_no'];
        $this->activityDestine->activity_id = $data['activity_id'];
        $this->activityDestine->mobile = $data['mobile'];
        $this->activityDestine->destine_amount = $data['destine_amount'];
        $this->activityDestine->pay_type = $data['pay_type'];
        $this->activityDestine->app_id = $data['app_id'];
        $this->activityDestine->channel_id = $data['channel_id'];
        $this->activityDestine->trade_no = $data['trade_no'];

        $this->activityDestine->destine_status = DestineStatus::DestineCreated;
        $this->activityDestine->create_time = time();
        $this->activityDestine->update_time = time();

        return $this->activityDestine->save();
    }


}