<?php
namespace App\Activity\Modules\Repository;

use App\Activity\Models\ActivityAppointment;
use App\Activity\Models\ActivityGoodsAppointment;
use Illuminate\Support\Facades\DB;

class ActivityAppointmentRepository
{

    protected $activityAppointment;


    public function __construct()
    {
        $this->activityAppointment = new ActivityAppointment();
    }

    /**
     * 创建活动
     * @param $data
     * @return bool
     */

    public static function add(array $data){
        $appointment_id = ActivityAppointment::query()->insertGetId($data);
        return $appointment_id;
    }


    /***
     * 获取活动信息
     * @return array
     */
    public static  function getActivityInfo(){
        $activityInfo = ActivityAppointment::query()->get()->toArray();
        if(!$activityInfo){
            return false;
        }
        return $activityInfo;
    }

}