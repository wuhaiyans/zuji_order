<?php
//路由映射 之收发货
return [

    //发货
    'warehouse.delivery.deliveryCreate' => 'DeliveryController@deliveryCreate', //创建
    'warehouse.delivery.cancel' => 'DeliveryController@cancel', //取消发货
    'warehouse.delivery.cancelDelivery' => 'DeliveryController@cancelDelivery', //取消发货
    'warehouse.delivery.receive' => 'DeliveryController@receive', //签收
    'warehouse.delivery.show' => 'DeliveryController@show', //清单
    'warehouse.delivery.imeis' => 'DeliveryController@imeis', //对应发货单imei列表
    'warehouse.delivery.send' => 'DeliveryController@send', //发货反馈
    'warehouse.delivery.logistics' => 'DeliveryController@logistics', //修改快递物流信息
    'warehouse.delivery.cancelMatch' => 'DeliveryController@cancelMatch', //取消配货
    'warehouse.delivery.addImei' => 'DeliveryController@addImei', //添加imei
    'warehouse.delivery.delImei' => 'DeliveryController@delImei', //删除imei
    'warehouse.delivery.list' => 'DeliveryController@list', //列表
    'warehouse.delivery.refuse' => 'DeliveryController@refuse', //拒签   待完成
];
