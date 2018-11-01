<?php
//路由映射 之预约活动
return [
    /********************************预约活动后台接口*********************************************/

    'api.activity.appointmentList'       => 'AppointmentController@appointmentList', //预约活动列表
    'api.activity.appointmentAdd'        => 'AppointmentController@appointmentAdd', //预约活动添加
    'api.activity.appointmentUpdate'     => 'AppointmentController@appointmentUpdate', //预约活动执行修改
    'api.activity.list.filter'            =>'AppointmentController@oppointmentListFilter', //预约活动列表筛选项接口


    //-+------------------------------------------------------------------------
    // | 活动预定接口
    //-+------------------------------------------------------------------------
    'api.activity.destine'             => 'ActivityDestineController@destine', //预约活动确认预约
    'api.activity.destineQuery'       => 'ActivityDestineController@destineQuery', //预约活动查询接口
    'api.activity.destineList'        => 'ActivityDestineController@destineList',  //【后台】预订信息列表
    'api.activity.destineDetailLog'        => 'ActivityDestineController@destineDetailLog',  //【后台】预订信息详情日志

    'api.advance.activityList'        => 'AdvanceActivityController@getList', //预约活动列表
    'api.advance.activityGet'         => 'AdvanceActivityController@get', //预约活动详情
    'api.advance.myAdvance'            => 'AdvanceActivityController@myAdvance', //我的预约


    //-+------------------------------------------------------------------------
    // | 1元活动体验预定接口
    //-+------------------------------------------------------------------------
    'api.experience.destine'             => 'ExperienceDestineController@experienceDestine', //活动体验支付接口
    'api.experience.destineQuery'        => 'ExperienceDestineController@experienceDestineQuery', //活动体验查询接口

    'api.experience.experienceDestineList' => 'ExperienceDestineController@experienceDestineList', //体验活动列表
    'api.experience.experienceDetail'      => 'ExperienceDestineController@experienceDetail', //体验活动列表 邀请详情

    
    //-+------------------------------------------------------------------------
    // | 预约退款
    //-+------------------------------------------------------------------------
    'api.activity.appointmentRefund'     => 'AppointmentController@appointmentRefund', //预约退款（15个自然日内）
    'api.activity.refund'                  => 'AppointmentController@refund', //预约退款（15个自然日后）
    'api.activity.test'                  => 'AppointmentController@test',





    /********************************************1元体验活动接口***************************************************/
    'api.activity.experienceList'       => 'ActivityExperienceController@experienceList', //1元体验活动列表
    'api.invite.numeration'              => 'ActiveInviteController@numeration', //注册邀请人数
    'api.invite.myInvite'                 => 'ActiveInviteController@myInvite', //我的邀请人数列表
    'api.activity.experienceRefund'     => 'ActivityExperienceController@experienceRefund', //预约退款（支付宝15个自然日内，微信退款）
    'api.activity.afterRefund'                 => 'ActivityExperienceController@refund', //预约退款（支付宝15个自然日后）


];
