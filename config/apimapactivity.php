<?php
//路由映射 之预约活动
return [
    /********************************预约活动后台接口*********************************************/

    'api.activity.appointmentList'      => 'AppointmentController@appointmentList', //预约活动列表
    'api.activity.appointmentAdd'       => 'AppointmentController@appointmentAdd', //预约活动添加
    'api.activity.appointmentUpdate'    => 'AppointmentController@appointmentUpdate', //预约活动修改
    'api.activity.appointmentUpdatePdo' => 'AppointmentController@appointmentUpdatePdo', //预约活动执行修改





];
