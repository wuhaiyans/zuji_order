<?php
namespace App\Lib;

class PublicInc{

    //判断低于多少分不可以下单
    const OrderScore = 50;


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
     * @var int 支付宝小程序支付
     */
    const  MiniAlipay = 5;
    /**
     * @var int 支付方式(银联支付)
     */
    const  UnionPay = 4;
    /**
     * @var int 支付方式(押金预授权)
     */
    const FlowerDepositPay = 3;
    /**
     * @var int 支付方式(花呗分期)
     */
    const FlowerStagePay = 2;
    /**
     * @var int 支付方式(预授权)
     */
    const WithhodingPay = 1;

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
     * @var int 确认收货后，退货申请有效期（天）
     */
    const Order_Return_Days =7;
    /**
     * @var int 应用ID (1是H5)
     */
    const Order_App_Id_H5 ='H5';

    /**
     * SKU 规格 成色 ID
     */
    const Sku_Spec_Chengse_Id = 1;

    /**
     * SKU 规格 租期 ID
     */
    const Sku_Spec_Zuqi_Id = 4;

    /**
     * 商品 品牌 苹果ID
     */
    const Goods_Brand_Id = 2;


    //文件上传秘钥
    const Api_Upload_Key = '8oxq1kma0eli9vlxnyj8v7qk335uvrf0';
    /**
     * 图片服务器地址
     */
    const Images_server_url = 'https://s1.huishoubao.com';
    /**
     * 图片上传地址
     */
    //const Api_Upload_File_Url = 'http://dev-psl-server.huanjixia.com/upload/handle';
    const Api_Upload_File_Url = 'http://push.huanjixia.com/upload/handle'; // 正式

    /**
     * @var string 支付宝默认应用好
     */
    const Alipay_App_Id = '2017102309481957';
    /**
     * 第三方登录授权 回跳地址
     */
    const Third_Party_Auth_Return_Url = 'https://h5-zuji.huishoubao.com/index.php?m=api&c=auth&a=code';
//
//    /**
//     * 支付--资金预授权 异步通知接口地址
//     */
//    const Payment_FundAuth_Notify_Url = 'https://api-zuji.huishoubao.com/api.php?m=api&c=fund_auth&a=notify';
//
//    /**
//     * 支付--资金预授权 解冻转支付异步通知
//     */
//    const Payment_FundAuth_Pay_Url = 'https://h5-zuji.huishoubao.com/alipay/createpay_notify_url.php';

    /*
     * 芝麻小程序APPID
     */
    const ZHIMA_MINI_APP_ID ='2018032002411058';

    /*
     * 订单是否支付在支付中KEY
     */
    const ORDER_PAYING ='ORDER_PAYING';

    /*
       * 订单是否在退款中
       */
    const ORDER_REFUNDING ='ORDER_REFUNDING';


}