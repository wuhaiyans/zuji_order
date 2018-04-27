<?php
/**
 * 
 * @author Administrator
 */
class ApiSubCode {
    
    /**
     * @var string 请求参数错误
     */
    const Successed = 'Successed';
    /**
     * @var string 请求参数错误
     */
    const Params_Error = 'Params.Error';
    
    /**
     * @var string 业务码：要求用户登录
     */
    const User_Unauthorized = 'User.Unauthorized';
    
    /**
     * @var string 禁止操作（用户无权操作当前内容）
     */
    const User_Forbidden = 'User.Forbidden';
    
    /**
     * @var string 注册失败
     */
    const User_Register_Error = 'User.Register.Error';
    
    /**
     * @var string 用户未实名认证
     */
    const User_Uncertified = 'User.Uncertified';
    /**
     * @var string 用户年龄 不否和
     */
    const User_Age_Error = 'User.Age.Error';
    
    /**
     * @var string 用户已经实名认证
     */
    const User_Has_Ccertified = 'User.Has.Certified';
    /**
     * @var string 修改密码失败
     */
    const User_Edit_Password_Error = 'User.Edit.Password.Error';
    
    /**
     * @var string 实名认证 参数错误
     */
    const Certivication_Param_Error = 'Certivication.Params.Error';
    /**
     * @var string 实名认证 认证失败
     */
    const Certivication_Failed = 'Certivication.Failed';
    /**
     * @var string 实名认证 订单编号错误
     */
    const Certivication_Order_no = 'Certivication.Order.No';
    //-+------------------------------------------------------------------------
    // | 内容
    //-+------------------------------------------------------------------------
    /**
     * @var string 内容位置ID错误
     */
    const Content_Position_id_Error = 'Content.Position_id.Error';
    /**
     * @var string 内容长度错误
     */
    const Content_Length_Error = 'Content.Length.Error';
    
    //-+------------------------------------------------------------------------
    // | 短信
    //-+------------------------------------------------------------------------
    /**
     * @var string 短信场景参数错误
     */
    const SMS_Error_Type = 'SMS.Error.type';
    /**
     * @var string 短信 手机号格式错误
     */
    const SMS_Error_Mobile = 'SMS.Error.mobile';
    /**
     * @var string 短信 国家编码错误
     */
    const SMS_Error_Country_code = 'SMS.Error.country_code';
    
    //-+------------------------------------------------------------------------
    // | 用户登录
    //-+------------------------------------------------------------------------
    /**
     * @var string 登录错误，非法操作
     */
    const Login_Error_Illegal = 'Login.Error.illegal';
    /**
     * @var string 登录 手机号格式错误
     */
    const Login_Error_Mobile = 'Login.Error.mobile';
    /**
     * @var string 登录 验证码格式错误
     */
    const Login_Error_Sm_code = 'Login.Error.sm_code';
    /**
     * @var string 登录 用户名错误
     */
    const Login_Error_Username = 'Login.Error.username';
    /**
     * @var string 登录 密码错误
     */
    const Login_Error_Password = 'Login.Error.password';
    /**
     * @var string 已经登录（重复操作提示）
     */
    const Login_Has_logon = 'Login.Has.logon';
    

    //-+------------------------------------------------------------------------
    // | 商品
    //-+------------------------------------------------------------------------
    /**
     * @var string 商品场景 id格式错误
     */
    const Sku_Error_Sku_id = 'Spu.Error.sku_id';
    /**
     * @var string 商品信息错误
     */
    const Sku_Error = 'Spu.Error';
    /**
     * @var string 商品不存在
     */
    const Spu_Not_Exists = 'Spu.Not_Exists';
    const Sku_Not_Exists = 'Sku.Not_Exists';
    
    const Sku_Zujin_Error = 'Sku.Zujin.Error';
    
    const Sku_Spec_Error = 'Sku.Specification.Error';

    //-+------------------------------------------------------------------------
    // | 订单列表
    //-+------------------------------------------------------------------------
    /**
     * @var string 订单 订单状态格式错误
     */
    const Order_Error_Status = 'Order.Error.status';
    /**
     * @var string 订单 订单编号格式错误
     */
    const Order_Error_Order_no = 'Order.Error.order_no';
    const Order_Error_Order_id = 'Order.Error.order_id';
    const Order_Error_Goods_id = 'Order.Error.goods_id';

     /**
     * @var string  订单创建失败
     */
    const Order_Creation_Failed = 'Order.Creation.Failed';
     /**
     * @var string  订单租期错误
     */
    const Order_Zuqi_Error = 'Order.Zuqi.Error';
     /**
     * @var string  订单租金错误
     */
    const Order_Zujin_Error = 'Order.Zujin.Error';
     /**
     * @var string  订单金额错误
     */
    const Order_Amount_Error = 'Order.Amount.Error';
    
     /**
     * @var string  订单已支付
     */
    const Order_Has_Traded = 'Order.Has.Traded';
    /**
     * @var string  订单超时
     */
    const Order_Timeout = 'Order.Timeout';
    
    /**
     * @var string 交易创建失败
     */
    const Trade_Creation_Error = 'Trade.Creation.Error';
    const Trade_Channel_Error = 'Trade.Channel.Error';
    const Trade_Url_Error = 'Trade.Url.Error';

    /**
     * 支付单 获取失败
     */
    const Order_Payment_Not_Exixts = 'Order.Payment.Not.Exixts';

    /**
     * 支付方式 获取失败
     */
    const Sku_Error_Payment_type_id = 'Sku.Error.Payment.type.id';
    //-+------------------------------------------------------------------------
    // | 退货
    //-+------------------------------------------------------------------------
    /**
     * @var string 退货 退货原因ID格式错误
     */
    const Retrun_Error_Reason_id = 'Retrun.Error.reason_id';
    /**
     * @var string 退货 物流单号格式错误
     */
    const Retrun_Error_Wuliu_channel_id= 'Retrun.Error.wuliu_channel_id';
    /**
     * @var string 退货 物流单号格式错误
     */
    const Retrun_Error_Wuliu_no= 'Retrun.Error.wuliu_no';
    /**
     * @var string 退货 损耗类型格式错误
     */
    const Retrun_Error_Loss_type = 'Retrun.Error.loss_type';

    //-+------------------------------------------------------------------------
    // | 收货地址
    //-+------------------------------------------------------------------------
    /**
     * @var string 收货地址 id格式错误
     */
    const Address_Error_Address_id = 'Address.Error.address_id';
    /**
     * @var string 收货地址 区或县id格式错误
     */
    const Address_Error_Country_id = 'Address.Error.country_id';
    /**
     * @var string 收货地址 	省ID格式错误
     */
    const Address_Error_Provin_id = 'Address.Error.provin_id';
    /**
     * @var string 收货地址 市id格式错误
     */
    const Address_Error_City_id = 'Address.Error.city_id';
    /**
     * @var string 收货地址 详情地址格式错误
     */
    const Address_Error_Address = 'Address.Error.address';
    /**
     * @var string 收货地址 姓名地址格式错误
     */
    const Address_Error_Name = 'Address.Error.name';
    /**
     * @var string 收货地址 手机号码格式错误
     */
    const Address_Error_Mobile = 'Address.Error.mobile';

    /**
     * @var string  imei号错误
     */
    const Order_Error_Imei = 'Order.Error.imei';
    /**
     * @var string  手持身份证照错误
     */
    const Order_Error_Card_hand = 'Order.Error.card_hand';
    /**
     * @var string  身份证正面照错误
     */
    const Order_Error_Card_positive = 'Order.Error.card_positive';
    /**
     * @var string  身份证反面照片错误
     */
    const Order_Error_Card_negative = 'Order.Error.card_negative';
    /**
     * @var string  商品合同照片错误
     */
    const Order_Error_Goods_delivery = 'Order.Error.goods_delivery';
    //-+------------------------------------------------------------------------
    // | 订单分期
    //-+------------------------------------------------------------------------
    /**
     * @var string 订单分期提前还款错误
     */
    const Order_Instalment_Error_prepayment = 'Order.Instalment.Error.prepayment';
}

