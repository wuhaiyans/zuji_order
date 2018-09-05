<?php
//路由映射 之预约活动
return [
    /********************************预约活动后台接口*********************************************/

    'api.activity.appointmentList'       => 'AppointmentController@appointmentList', //预约活动列表
    'api.activity.appointmentAdd'        => 'AppointmentController@appointmentAdd', //预约活动添加
    'api.activity.appointmentUpdate' => 'AppointmentController@appointmentUpdate', //预约活动执行修改


    //-+------------------------------------------------------------------------
    // | 活动预定接口
    //-+------------------------------------------------------------------------
    'api.activity.destine'      => 'ActivityDestineController@destine', //预约活动确认预约
    'api.activity.destineQuery'      => 'ActivityDestineController@destineQuery', //预约活动查询接口
    'api.advance.activityList'      => 'AdvanceActivityController@getList', //预约活动列表
    'api.advance.activityGet'      => 'AdvanceActivityController@get', //预约活动详情
    'api.advance.myAdvance'     => 'AdvanceActivityController@myAdvance', //我的预约



    //-+------------------------------------------------------------------------
    // | 预约退款
    //-+------------------------------------------------------------------------
    'api.activity.appointmentRefund'     => 'AppointmentController@appointmentRefund', //我的预约


];
