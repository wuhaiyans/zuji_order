<?php
namespace App\Activity\Modules\Repository;

use App\Activity\Models\ActivityExperience;


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
         $experienceList=ActivityExperience::query()->orderBy('create_time', 'DESC')-> get();
         if(!$experienceList){
             return false;
         }
         return $experienceList;
    }


}