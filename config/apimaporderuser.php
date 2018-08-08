<?php
//路由映射 之第三方平台下单用户管理
return [

    //第三方平台下单用户

    // 后台
    'orderuser.thirdpartyuser.lists' => 'ThirdPartyUserController@lists', //列表
    'orderuser.thirdpartyuser.add' => 'ThirdPartyUserController@add', //添加
    'orderuser.thirdpartyuser.update' => 'ThirdPartyUserController@update', //修改
    'orderuser.thirdpartyuser.matching' => 'ThirdPartyUserController@matching', //根据新增数据查询相似订单
    'orderuser.thirdpartyuser.publics' => 'ThirdPartyUserController@publics', //公共数据
    'orderuser.thirdpartyuser.del' => 'ThirdPartyUserController@del', //删除
    'orderuser.thirdpartyuser.audit' => 'ThirdPartyUserController@audit', //审核通过
    'orderuser.thirdpartyuser.getRow' => 'ThirdPartyUserController@getRow', //根据ID查询一条数据
    'orderuser.thirdpartyuser.importExcel' => 'ThirdPartyUserController@importExcel', //导入已下单用户execl表

    // 下单接口
    'orderuser.thirdpartyuser.orderMatching' => 'ThirdPartyUserController@orderMatching', //H5、小程序、App下单匹配

    // 定时任务
    'orderuser.thirdpartyuser.start' => 'ThirdPartyUserController@start', //定时任务开始时间
    'orderuser.thirdpartyuser.end' => 'ThirdPartyUserController@end', //定时任务结束时间


];
