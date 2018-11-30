<?php
namespace App\Activity\Modules\Repository;

use App\Activity\Models\ActivityExperience;
use Illuminate\Support\Facades\DB;


class ActivityExperienceRepository
{

    protected $activityExperience;


    public function __construct()
    {
        $this->activityExperience = new ActivityExperience();
    }

    /**
     * 获取体验活动信息
     */
    public static function getActivityExperienceInfo(){
        $experienceList = DB::table('order_activity_experience')
            ->leftJoin('order_activity_theme','order_activity_experience.activity_id', '=', 'order_activity_theme.activity_id')
            ->select("*")
            ->orderBy('order_activity_experience.create_time', 'DESC')
            ->get();
        // $experienceList=ActivityExperience::query()->orderBy('create_time', 'DESC')-> get();
         if(!$experienceList){
             return false;
         }
         return $experienceList;
    }


}