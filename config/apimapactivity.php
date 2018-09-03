<?php
//路由映射 之预约活动
return [
    /********************************预约活动后台接口*********************************************/

    'api.activity.appointmentList'      => 'AppointmentController@appointmentList', //预约活动列表
    'api.activity.appointmentAdd'       => 'AppointmentController@appointmentAdd', //预约活动添加
    'api.activity.appointmentUpdate'    => 'AppointmentController@appointmentUpdate', //预约活动修改
    'api.activity.appointmentUpdatePdo' => 'AppointmentController@appointmentUpdatePdo', //预约活动执行修改


    //-+------------------------------------------------------------------------
    // | 活动预定接口
    //-+------------------------------------------------------------------------
    'api.activity.destine'      => 'ActivityDestineController@destine', //预约活动确认预约
    'api.advance.activityList'      => 'AdvanceActivityController@getList', //预约活动列表
    'api.advance.activityGet'      => 'AdvanceActivityController@get', //预约活动详情
    'api.advance.myAdvance'     => 'AdvanceActivityController@myAdvance', //我的预约





];
