<?php
namespace App\Lib;
/**
 * 旧系统 原有配置信息
 * User: wuhaiyan
 * DateTime: 2018/5/4 11:23
 */
class OldInc {

    /**
     * @var int 免押最小芝麻分
     */
    const ZhiMa_Score = 600;
    /**
     * @var int 芝麻分过期时间间隔（秒）
     */
    const ZhiMa_Score_Tnterval = 900;// 900 = 15分钟
    /**
     * @var int 京东小白信用渠道appid
     */
    const Jdxbxy_App_id = 39;
    /**
     * @var int 京东小白信用允许下单的最低小白信用分值
     */
    const Jdxbxy_Score = 80;
    /**
     * @var int 分页大小
     */
    const Page_Size = 20;
    /**
     * @var string 客服电话
     */

    const Customer_Service_Phone ="400-080-9966";
    /**
     * @var int 订单超时时间 单位(小时)
     */
    const Order_TimeOut_Hours =24;
    /**
     * @var int 租用设备的最小年龄
     */
    const Order_Zuji_Min_Age =18;
    /**
     * @var int 租用设备的最大年龄
     */
    const Order_Zuji_Max_Age =50;
    /**
     * @var int 定义即将结束订单的时间  当前时间之前 多少 (单位)天
     */
    const Order_To_close_day =365;
    /**
     * @var int 允许一周之内退款次数
     */
    const Order_Refund_Num =5;
    /**
     * @var int 订单支付超时时间
     */
    const Order_Pay_Time_Out= 2;
    /**
     * @var int 自动确认收货时间
     */
    const Order_Confirm_Days =7;
    /**
     * SKU 规格 成色 ID
     */
    const Sku_Spec_Chengse_Id = 1;

    /**
     * SKU 规格 租期 ID
     */
    const Sku_Spec_Zuqi_Id = 4;

    const ZUJIN_MIN_PRICE=0.01;

    /**
     * 商品必选个规格ID列表
     * @return array
     */
    public static function getMustSpecIdList(){
        return [self::Sku_Spec_Chengse_Id,  self::Sku_Spec_Zuqi_Id];
    }

}
