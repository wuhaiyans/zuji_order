<?php
//路由映射 营销工具
return [
//************************************************优惠券************************************************//

        //优惠券后台
        'api.tool.coupon.detail'=>'CouponBackendController@detail',
        'api.tool.coupon.create'=>'CouponBackendController@create',
        'api.tool.coupon.publish'=>'CouponBackendController@publish',
        'api.tool.coupon.unPublish'=>'CouponBackendController@unPublish',
        'api.tool.coupon.remove'=>'CouponBackendController@remove',
        'api.tool.coupon.greyTest'=>'CouponBackendController@greyTest',
        'api.tool.coupon.list'=>'CouponBackendController@list',
        'api.tool.coupon.getCode'=>'CouponBackendController@getCode',
        'api.tool.coupon.importUser'=>'CouponBackendController@importUser',
        
        //优惠券客户端
        'api.tool.coupon.couponUserList'=>'CouponFrontendController@couponUserList',
        'api.tool.coupon.spuCouponList'=>'CouponFrontendController@spuCouponList',
        'api.tool.coupon.couponListWhenOrder'=>'CouponFrontendController@couponListWhenOrder',
        'api.tool.coupon.couponListWhenPay'=>'CouponFrontendController@couponListWhenPay',
        'api.tool.coupon.couponUserExchange'=>'CouponFrontendController@couponUserExchange',
        'api.tool.coupon.couponUserReceive'=>'CouponFrontendController@couponUserReceive',
        'api.tool.coupon.couponUserWriteOff'=>'CouponFrontendController@couponUserWriteOff',
        'api.tool.coupon.couponUserCancel'=>'CouponFrontendController@couponUserCancel',
        'api.tool.coupon.couponUserDetail'=>'CouponFrontendController@couponUserDetail',
];
