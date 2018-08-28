<?php

namespace App\Lib\Wechat;


/**
 * 微信接口封装
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class WechatApi {
    
    /**
     * 获取 用户授权token access_token
	 * @access public
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
     * @param string $appid		应用ID
     * @param string $secret	应用密钥
     * @return array
	 * [
	 *		'access_token'	=> '', // token值
	 *		'expires_in'	=> '', // 有效期，单位：秒
	 * ]
	 * @throws WechatApiException
     */
    public static function getUserAccessToken( string $appid, string $secret, string $code ){ 
        $url_get='https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$appid.'&secret='.$secret.'&code='.$code.'&grant_type=authorization_code';
		$result = self::_curl_get($url_get);
		//$result = '{"access_token":"13_IwYHyTE0AImxx-E8WhqNTRB9hUFHKvySqwY1kF1uLkrIo6CXEnBrU0LwVSLIrQ_FOv52G-dvgzihI9wwSw-BWw","expires_in":7200,"refresh_token":"13_Ra5ZgG9zEIsJ1sWeAdtkb9LzeL5IwC9FuIc3R3q3ebn7ec0rXEnxu2AXqt_nfz6hQy7ayUYH5nhGLYvCv1OXwg","openid":"oBjc20uu9n0R_uv2yAzRA0YHSVIs","scope":"snsapi_userinfo"}';
		if( empty($result) ){
			throw new WechatApiException('getUserAccessToken',['appid'=>$appid,'secret'=>$secret,'code'=>$code]);
		}
		$data = json_decode($result,true);
		if( is_null($data) ){
			throw new WechatApiException('getUserAccessToken',['appid'=>$appid,'secret'=>$secret,'code'=>$code],'解析错误');
		}
		if( isset($data['errcode']) ){
			throw new WechatApiException('getUserAccessToken',['appid'=>$appid,'secret'=>$secret,'code'=>$code],'微信错误',$data);
		}
		if( !isset($data['access_token']) ){
			throw new WechatApiException('getUserAccessToken',['appid'=>$appid,'secret'=>$secret,'code'=>$code],'access_token错误',$data);
		}
		return [
			'access_token'	=> $data['access_token'],
			'refresh_token'	=> $data['refresh_token'],
			'expires_in'	=> $data['expires_in'],
			'openid'	=> $data['openid'],
		];
    }
	
	/**
	 * 读取 微信用户信息
	 * @param string $token
	 * @param string $openid
	 * @return array
	 * [
	 *		'openid'	=> '',		// 必须
	 *		'nickname'	=> '',	//
	 *		'sex'		=> '',//
	 *		'province'	=> '',//
	 *		'city'		=> '',//
	 *		'country'	=> '',//
	 *		'headimgurl'=> '',//
	 *		'unionid'	=> '',//【可选】
	 * ]
	 * @throws WechatApiException
	 */
    public static function getUserInfo( string $token, string $openid ):array{ 
		
        $url_get='https://api.weixin.qq.com/sns/userinfo?access_token='.$token.'&openid='.$openid.'&lang=zh_CN';
		$result = self::_curl_get($url_get);
		//$result = '{"openid":"oBjc20uu9n0R_uv2yAzRA0YHSVIs","nickname":"刘红星","sex":1,"language":"zh_CN","city":"朝阳","province":"北京","country":"中国","headimgurl":"http:\/\/thirdwx.qlogo.cn\/mmopen\/vi_32\/Q0j4TwGTfTK98V6YdT9CJYuDPqsJkj7razFLaMfcnFRewW1X9GichmUQhP2ibJOTcvD43xskMBQxBwbHSSibetQfg\/132","privilege":[]}';
		if( empty($result) ){
			throw new WechatApiException('getUserInfo',['access_token'=>$token,'openid'=>$openid]);
		}
		$data = json_decode($result,true);
		if( is_null($data) ){
			throw new WechatApiException('getUserInfo',['access_token'=>$token,'openid'=>$openid],'解析错误');
		}
		if( isset($data['errcode']) ){
			throw new WechatApiException('getUserInfo',['access_token'=>$token,'openid'=>$openid],'微信错误',$data);
		}
		if( !isset($data['openid']) ){
			throw new WechatApiException('getUserInfo',['access_token'=>$token,'openid'=>$openid],'微信错误',$data);
		}
		return $data;
	}
	
    /**
     * 获取 access_token
	 * @access public
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
     * @param string $appid		应用ID
     * @param string $secret	应用密钥
     * @return array
	 * [
	 *		'access_token'	=> '', // token值
	 *		'expires_in'	=> '', // 有效期，单位：秒
	 * ]
	 * @throws WechatApiException
     */
    public static function getApiAccessToken( string $appid, string $secret ){ 
        $url_get='https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$appid.'&secret='.$secret;
        
		$result = self::_curl_get($url_get);
		if( empty($result) ){
			throw new WechatApiException('token',['appid'=>$appid,'secret'=>$secret]);
		}
		$data = json_decode($result,true);
		if( is_null($data) ){
			throw new WechatApiException('token',['appid'=>$appid,'secret'=>$secret],'解析错误');
		}
		if( !isset($data['access_token']) ){
			throw new WechatApiException('token',['appid'=>$appid,'secret'=>$secret],'access_token错误',$data);
		}
		return [
			'access_token'	=> $data['access_token'],
			'expires_in'	=> $data['expires_in'],
		];
    }
    
    /**
     * 获取 jsapi_ticket
	 * @access public
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
     * @param string $token		token值
     * @return array
	 * [
	 *		'ticket'		=> '', // ticket值
	 *		'expires_in'	=> '', // 有效期，单位：秒
	 * ]
	 * @throws WechatApiException
     */
    public static function getJsapiTicket( string $token ){ 
		$url_get='https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token='.$token;
		$result = self::_curl_get($url_get);
		if( empty($result) ){
			throw new WechatApiException('getticket',['token'=>$token]);
		}
		$data = json_decode($result,true);
		if( is_null($data) ){
			throw new WechatApiException('getticket',['token'=>$token],'解析错误');
		}
		if( $data['errcode'] != 0 ){
			throw new WechatApiException('getticket',['token'=>$token],'errcode错误');
		}
		return $data;
    }
    
    /**
     * 发送get请求
	 * @access private
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
     * @param type $url
     * @return type
     */
    private static function _curl_get($url){
        try {
                $ch = curl_init();
//    		$header = "Accept-Charset: utf-8";
    		curl_setopt($ch, CURLOPT_URL, $url);
////    		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
////    		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
////    		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
////    		curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
////    		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
////    		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
////    		curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
////    		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    		$temp = curl_exec($ch);
                curl_close($ch);
    		return $temp;
            
        } catch (Exception $exc) {
            echo 'WechatApi::_curl_get()错误！';exit;
        }

    }

}
