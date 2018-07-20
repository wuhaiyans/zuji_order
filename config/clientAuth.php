<?php
/**
 *  需要登录验证token的接口 针对订单clientApi
 * Author: wutiantang
 * Email :wutiantang@huishoubao.com.cn
 * Date: 2018/6/8 0008
 * Time: 下午 8:38
 */

return [
        //无需验证
        'exceptAuth' => [
            //提前还款
            'api.Withhold.repayment',
            'api.order.checkVerifyApp',
            ]
    ];



