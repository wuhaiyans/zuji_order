<?php
//路由映射 之收发货
return [

    //imei导入
    'warehouse.imei.import' => 'ImeiController@import', //导入imei
    'warehouse.imei.list' => 'ImeiController@list', //列表


    //物流
    'warehouse.delivery.logisticList' => 'DeliveryController@logisticList', //取物流列表
    'warehouse.delivery.logisticName' => 'DeliveryController@logisticName', //取物流名称

    //发货
    'warehouse.delivery.deliveryCreate' => 'DeliveryController@deliveryCreate', //发货单 -- 创建
    'warehouse.delivery.matchGoods' => 'DeliveryController@matchGoods', //发货清单 -- 配货

    'warehouse.delivery.cancel' => 'DeliveryController@cancel', //取消发货
    'warehouse.delivery.cancelDelivery' => 'DeliveryController@cancelDelivery', //取消发货
    'warehouse.delivery.receive' => 'DeliveryController@receive', //签收
    'warehouse.delivery.show' => 'DeliveryController@show', //清单
    'warehouse.delivery.imeis' => 'DeliveryController@imeis', //对应发货单imei列表
    'warehouse.delivery.send' => 'DeliveryController@send', //发货反馈
    'warehouse.delivery.logistics' => 'DeliveryController@logistics', //修改快递物流信息
    'warehouse.delivery.match' => 'DeliveryController@match', //配货
    'warehouse.delivery.cancelMatch' => 'DeliveryController@cancelMatch', //取消配货
    'warehouse.delivery.addImei' => 'DeliveryController@addImei', //添加imei
    'warehouse.delivery.delImei' => 'DeliveryController@delImei', //删除imei
    'warehouse.delivery.list' => 'DeliveryController@list', //列表
    'warehouse.delivery.refuse' => 'DeliveryController@refuse', //拒签   待完成


    //收货 待完成

    'warehouse.receive.checkItems'=> 'ReceiveController@checkItems', //取消收货
    'warehouse.receive.cancelReceive'=> 'ReceiveController@cancelReceive', //取消收货
    'warehouse.receive.list'=> 'ReceiveController@list', //列表
    'warehouse.receive.create'=> 'ReceiveController@create', //创建
    'warehouse.receive.cancel'=> 'ReceiveController@cancel', //取消
    'warehouse.receive.receiveDetail'=> 'ReceiveController@receiveDetail', //收货
    'warehouse.receive.received'=> 'ReceiveController@received', //收货
    'warehouse.receive.calcelReceive'=> 'ReceiveController@calcelReceive', //改变收货状态为未收货
    'warehouse.receive.check'=> 'ReceiveController@check',//验收，针对设备
    'warehouse.receive.cancelCheck'=> 'ReceiveController@cancelCheck',//验收取消，针对设备
    'warehouse.receive.finishCheck'=> 'ReceiveController@finishCheck',//验收完成，针对收货单
    'warehouse.receive.show'=> 'ReceiveController@show',//清单查询，针对收货单
    'warehouse.receive.note'=> 'ReceiveController@note'//录入检测项，针对收货单
];
