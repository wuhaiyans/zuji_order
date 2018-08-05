<?php
//路由映射 之第三方平台下单用户管理
return [

    //第三方平台下单用户
    'orderuser.thirdpartyuser.lists' => 'ThirdPartyUserController@lists', //列表
    'orderuser.thirdpartyuser.add' => 'ThirdPartyUserController@add', //添加
    'orderuser.thirdpartyuser.update' => 'ThirdPartyUserController@updatess', //修改
    'orderuser.thirdpartyuser.matching' => 'ThirdPartyUserController@matching', //根据新增数据查询相似订单
    'orderuser.thirdpartyuser.publics' => 'ThirdPartyUserController@publics', //公共数据


];
