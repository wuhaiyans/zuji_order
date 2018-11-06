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
        $experienceList = DB::table('activity_experience')
            ->leftJoin('activity_theme','activity_experience.activity_id', '=', 'activity_theme.activity_id')
            ->select('activity_experience.* ','activity_theme.begin_time','activity_theme.end_time','activity_theme.opening_time')
            ->orderBy('create_time', 'DESC')-> get();
        // $experienceList=ActivityExperience::query()->orderBy('create_time', 'DESC')-> get();
         if(!$experienceList){
             return false;
         }
         return $experienceList;
    }


}