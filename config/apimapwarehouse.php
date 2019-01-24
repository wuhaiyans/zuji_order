<?php
//路由映射 之收发货
return [

    //imei导入
    'warehouse.imei.export' => 'ImeiController@export', //导出
    'warehouse.imei.search' => 'ImeiController@search', //列表
    'warehouse.imei.import' => 'ImeiController@import', //导入imei
    'warehouse.imei.list' => 'ImeiController@list', //列表
    'warehouse.imei.importFromExcel' => 'ImeiController@importFromExcel', //导入
    'warehouse.imei.downTpl' => 'ImeiController@downTpl', //列表
    'warehouse.imei.publics' => 'ImeiController@publics', //公用数据
    'warehouse.imei.getRow' => 'ImeiController@getRow', //根据IMEI查询返回一条记录
    'warehouse.imei.setRow' => 'ImeiController@setRow', //根据IMEI ID修改一条记录
    'warehouse.imei.getImeiLog' => 'ImeiController@getImeiLog', //查询IMEI日志


    //物流
    'warehouse.delivery.logisticList' => 'DeliveryController@logisticList', //取物流列表
    'warehouse.delivery.logisticName' => 'DeliveryController@logisticName', //取物流名称

    //发货
    'warehouse.delivery.deliveryCreate' => 'DeliveryController@deliveryCreate', //发货单 -- 创建
    'warehouse.delivery.matchGoods' => 'DeliveryController@matchGoods', //发货清单 -- 配货
    'warehouse.delivery.cancelMatchGoods' => 'DeliveryController@cancelMatchGoods', //取消单品配货
    'warehouse.delivery.getStatus' => 'DeliveryController@getStatus', //根据订单获取发货单状态是否属于已配货

    'warehouse.delivery.cancel' => 'DeliveryController@cancel', //取消发货
    'warehouse.delivery.auditFailed' => 'DeliveryController@auditFailed', //取消发货后,退货退款审核未通过,继续发货
    'warehouse.delivery.cancelDelivery' => 'DeliveryController@cancelDelivery', //取消发货
    'warehouse.delivery.receive' => 'DeliveryController@receive', //签收
    'warehouse.delivery.show' => 'DeliveryController@show', //发货清单列表
    'warehouse.delivery.imeis' => 'DeliveryController@imeis', //对应发货单imei列表
    'warehouse.delivery.send' => 'DeliveryController@send', //发货操作
    'warehouse.delivery.channelSend' => 'DeliveryController@channelSend', //渠道自己发货反馈
    'warehouse.delivery.logistics' => 'DeliveryController@logistics', //修改快递物流信息
    'warehouse.delivery.match' => 'DeliveryController@match', //配货
    'warehouse.delivery.cancelMatch' => 'DeliveryController@cancelMatch', //取消配货
    'warehouse.delivery.addImei' => 'DeliveryController@addImei', //添加imei
    'warehouse.delivery.delImei' => 'DeliveryController@delImei', //删除imei
    'warehouse.delivery.list' => 'DeliveryController@lists', //列表
    'warehouse.delivery.refuse' => 'DeliveryController@refuse', //拒签   待完成
    'warehouse.delivery.publics' => 'DeliveryController@publics', //发货公用参数
    'warehouse.delivery.export' => 'DeliveryController@export', //导出excel

    'warehouse.delivery.statistics' => 'DeliveryController@statistics', //最终数量统计


    //收货 待完成
    'warehouse.receive.checkItems'=> 'ReceiveController@checkItems', //检测项
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
    'warehouse.receive.note'=> 'ReceiveController@note',//录入检测项，针对收货单
    'warehouse.receive.checkItemsFinish'=> 'ReceiveController@checkItemsFinish',//检测完成
    'warehouse.receive.logistics'=> 'ReceiveController@logistics',//修改物流
    'warehouse.receive.createDelivery'=> 'ReceiveController@createDelivery',//换货操作
    'warehouse.receiveGoods.list'=> 'ReceiveGoodsController@list',//设备列表
    'warehouse.receiveGoods.publics'=> 'ReceiveGoodsController@publics',//公用参数
    'warehouse.receiveGoods.checkItems'=> 'ReceiveGoodsController@checkItems',//取检测项
    'warehouse.receive.imeiIn'=> 'ReceiveController@imeiIn', //确认入库
    'warehouse.receive.orderImeiIn'=> 'ReceiveController@orderImeiIn', //确认入库(订单工具)
    'warehouse.checkitem.getDetails'=> 'CheckItemController@getDetails', //查看检测详情

    //线下门店
    'warehouse.delivery.deliveryNum'=> 'DeliveryController@deliveryNum', //线下门店待发货数量
    'warehouse.delivery.xianxiaList'=> 'DeliveryController@xianxiaList', //线下门店待发货列表
    'warehouse.delivery.xianxiaDelivery'=> 'DeliveryController@xianxiaDelivery', //线下门店发货
    'warehouse.delivery.xianxiaShow'=> 'DeliveryController@xianxiaShow', //线下发货清单(发货详情)

    'warehouse.receive.xianxiaCheckItemsFinish'=> 'ReceiveController@xianxiaCheckItemsFinish', //线下门店检测完成
    'warehouse.checkitem.getXiannxiaDetails'=> 'CheckItemController@getXiannxiaDetails', //线下门店查看检测详情
    'warehouse.checkitem.receiveNum'=> 'CheckItemController@receiveNum', //线下门店待检测数量
    'warehouse.checkitem.reviewButton'=> 'CheckItemController@reviewButton', //线下门店根据订单号查询是否显示检测,检测结果按钮
    'warehouse.checkitem.xianxiaCheck'=> 'CheckItemController@xianxiaCheck', //线下门店待检测列表
    'warehouse.checkitem.getPublic'=> 'CheckItemController@getPublic', //线下门店检测公共参数




];
