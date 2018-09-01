<?php
namespace App\Activity\Modules\Repository;

use App\Activity\Models\ActivityAppointment;
use App\Activity\Models\ActivityGoodsAppointment;

class ActivityDestineRepository
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

    public function add($data){

        return $this->activityAppointment->save();
    }


}