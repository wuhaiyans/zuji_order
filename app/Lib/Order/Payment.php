<?php
/**
 * 订单支付接口文件
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 * 
 */
namespace App\Lib\Order;
use App\Lib\Curl;

/**
 * Payment 订单支付接口
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class Payment{
        
    /**
     * 支付通知回调接口
     * @access public
     * @author liuhongxing <liuhongxing@huishoubao.com.cn>
     * @param	array	$params		支付通知参数
     * @return	bool	true：通知发送；false：通知发送失败
     */
    public static function paymentNotify( array $params ){
		$api_url = '';
        $response = Curl::post($base_api, [
            'appid'=> 1,
            'version' => 1.0,
            'method'=> 'order.pay.notify',
            'data' => $params
        ]);

        return $response;
	//-+--------------------------------------------------------------------
	// | 业务处理（根据请求参数，进行相应的业务处理）
	//-+--------------------------------------------------------------------
	// todo（调用业务相关的接口，进行逻辑组合）
	// 1）（做什么，获取什么值）
	// 2）（做什么，获取什么值）
	// 3）（做什么，获取什么值）
	// ...
	// 4）（获取到业务处理最后结果）
	
	//-+--------------------------------------------------------------------
	// | 业务结果返回
	//-+--------------------------------------------------------------------
	// todo （返回结果）(如果需要，就对业务处理结果进行格式化处理)
    }
    //
    // @param 常用分析
    // 1）具体类型 
    //	    @params int	    $name	【必须/可选】（描述）
    //	    @params string  $name	【必须/可选】（描述）
    //	    @params boolean $name	【必须/可选】（描述）
    //	    @params array   $name	【必须/可选】（描述）
    //	    @params [类]    $name	【必须/可选】（描述）
    // 2）混合类型:
    //	    @params mixed    array|int	       
    //	    @params mixed    array|string
    // 3）良好习惯
    //	    当入参列表有多个时，最好选择 关联数组 的形式 传递参数，方便以后的扩张，减少对方法的声明的修改
    //	    例如：一个方法需要的参数如下：
    //	    @params int	    $id		【必须】用户ID
    //	    @params string  $username	【必须】用户名
    //	    @params string  $tel	【可选】联系方式
    //	    @params string  $address	【可选】联系地址
    //	    可以修改为：
    //	    @params array   $userInfo   【必须】用户信息
    //	    array(
    //		'id' => '',		//【必须】用户ID
    //		'username' => '',	//【必须】用户名
    //		'tel' => '',		//【可选】联系方式
    //		'address' => '',	//【可选】联系地址
    //	    )
    //	    【注意：】
    //	    1）接口不负责校验参数，接口只承诺：如果按照声明调用，就可以处理相应的业务，并返回预期的结果
    //	    2）要求调用方法的程序，做参数校验，然后再调用
    //	    
    // @return 常用分析
    // 1）具体类型
    // 2）混合类型：
    //	    @return mixed   boolean|array   array：成功（注明具体格式）；false：失败
    //		如果返回值为关联数组，必须注明数组的键名称和值的约束
    //		例如：array(
    //		    'key1' => 'ABC'， //【必须】（描述）
    //		    'key2' => 'xxx'，   //【可选】，默认值：xxx；（描述）
    //		    'key1' => array()， //【必须】（描述）
    //		)
    //	    @return mixed   boolean|int	    int：成功（注明int取值范围）；false：失败
    
    
}
