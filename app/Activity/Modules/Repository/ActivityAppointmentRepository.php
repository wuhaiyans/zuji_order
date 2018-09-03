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
     * [
     * 'title'             =>'',  标题           string    【必传】
     * 'appointment_price' =>'',  预定金额       string 【必传】
     * 'appointment_image' =>'',  活动图片       string 【必传】
     * 'desc'              =>'',  活动描述       string 【必传】
     * 'begin_time'        =>'',  活动开始时间   int    【必传】
     * 'end_time'          =>''   活动结束时间   int 【必传】
     * 'appointment_status' =>'', 活动状态      string 【必传】
     * ]
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
    public static  function getActivityInfo($page,$size){
        $activityInfo = ActivityAppointment::query()->select('activity_appointment.*')
            ->orderBy('create_time', 'DESC')
            ->paginate($size,$columns = ['*'], $pageName = 'page', $page);
        if(!$activityInfo){
            return false;
        }
        return $activityInfo;
    }

}