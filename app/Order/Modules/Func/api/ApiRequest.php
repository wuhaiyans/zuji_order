<?php
/**
 * API 请求对象
 * 定义了接口请求交互协议
 * 用于:
 * 1）接收请求；
 * 2）发送请求；
 * @access public
 * @author Liuhongxing <liuhongxing@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 */
class ApiRequest {

    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';

    private $appid = '';
    private $method = '';
    private $timestamp = '';
    private $version = '1.0';
    private $auth_token = '';
    private $params = [];
    private $sign_type = '';
    private $sign = '';
    private $url = '';


    public function __construct() {
    }

    /**
     * 获取 用户回话标识
     * @return string  允许为空字符串
     */
    public function getAuthToken( ){
        return $this->auth_token;
    }

    public function getAppid( ){
        return $this->appid;
    }

    public function getSign( ){
        return $this->sign;
    }

    public function getMethod( ){
        return $this->method;
    }

    public function setUrl( $url ){
        return $this->url = $url;
    }
    /**
     * 获取业务参数
     * @return array
     */
    public function getParams( ){
        return $this->params;
    }

    /**
     * 设置参数
     * @param array|string $params
     * @throws \Exception
     */
    public function setParams( $params ){
        if( !is_string($params) && !is_array($params) ){
            $params = [];
            //throw new \Exception('method param error');
        }
    	if( is_string($params) ){
    	    $params = json_decode($params,true);
    	}
        $this->params = $params;
    }

    /**
     * 发送请求
     * @param string $method
     * @return ApiResponse
     * @throws \Exception
     */
    public function send($method=self::METHOD_POST ){
        if( $this->url == '' ){
            throw new \Exception('ApiRequest illegal state');
        }
        $jsonStr = '';
        if( $method == self::METHOD_POST ){
            $jsonStr = Curl::post($this->url, $this->toString());
        }elseif( $method == self::METHOD_GET ){
            $jsonStr = Curl::get($this->url, $this->toString());
        }
        $Response = new ApiResponse($jsonStr);
        return $Response;
    }
    /**
     * 发送 POST请求
     * @return ApiResponse
     * @throws \Exception
     */
    public function sendPost(){
        $data = $this->send(self::METHOD_POST);
        return $data;
    }
    /**
     * 发送 GET请求
     * @return ApiResponse
     * @throws \Exception
     */
    public function sendGet(){
        $this->send(self::METHOD_GET);
    }

    /**
     * 创建一个请求
     * @param string|array $jsonStr 【必须】字符串是必须是标准的json；数组必须是关联数组
     * 不管是json还是关联数组，必须包含：
     * appid, method, timestamp, version, params, sign_type, sign
     * @return ApiStatus
     */
    public function create($jsonStr){
        $status = new ApiStatus();
        $status->success();// 默认为成功状态
        $this->status = $status;

	//-+--------------------------------------------------------------------
	// | 解析响应数据
	//-+--------------------------------------------------------------------
        if(strlen($jsonStr)==0){
            $status->setCode(ApiStatus::CODE_10100)->setMsg('空请求');
            return $status;
        }
        $data  = json_decode($jsonStr,true);
        if( !is_array($data) ){
            $status->setCode(ApiStatus::CODE_10101)->setMsg('非json格式');
            return $status;
        }

	//-+--------------------------------------------------------------------
	// | 校验参数
	//-+--------------------------------------------------------------------
        // appid 参数
        if( !isset($data['appid']) ){
            $status->setCode(ApiStatus::CODE_10102)->setMsg('code参数缺失');
            return $status;
        }
        if( !isset($data['appid']) ){
            $status->setCode(ApiStatus::CODE_10102)->setMsg('appid错误');
            return $status;
        }
        // method 参数
        if( !isset($data['method']) ){
            $status->setCode(ApiStatus::CODE_10103)->setMsg('method参数缺失');
            return $status;
        }
        // timestamp 参数
        if( !isset($data['timestamp']) || !is_string($data['timestamp']) || strtotime($data['timestamp'])==-1 ){
            $status->setCode(ApiStatus::CODE_10104)->setMsg('timestamp参数缺失或不是正确的时间格式');
            return $status;
        }
        // version 参数
        if( !isset($data['version']) || !is_string($data['version']) ){
            $status->setCode(ApiStatus::CODE_10105)->setMsg('version错误');
            return $status;
        }

        // params 参数
        if( !isset($data['params']) ){
            if(  is_string( $data['params']) ){
                $data['params'] = json_decode($data['params'],true);
            }
            if(  !is_array($data['params'])  ){
                $status->setCode(ApiStatus::CODE_10106)->setMsg('params错误');
                return $status;
            }
        }

        // sign_type 参数
        if( !isset($data['sign_type']) || !is_string($data['sign_type']) ){
            $status->setCode(ApiStatus::CODE_10107)->setMsg('sign_type错误');
            return ;
        }

        // sign 参数
        if( !isset($data['sign']) || !is_string($data['sign']) ){
            $status->setCode(ApiStatus::CODE_10108)->setMsg('sign错误');
            return $status;
        }


	//-+--------------------------------------------------------------------
	// | 数字验签
	//-+--------------------------------------------------------------------
        if( !ApiUtil::verify($data['params'], $data['sign'], 'ZUJI-PRIVATE-KEY') ){
            $status->setCode(ApiStatus::CODE_10109)->setMsg('签名错误');
            return $status;
        }

	//-+--------------------------------------------------------------------
	// | 赋值，初始化响应对象
	//-+--------------------------------------------------------------------
        $this->appid = $data['appid'];
        $this->method = $data['method'];
        $this->timestamp = $data['timestamp'];
        $this->version = $data['version'];
        $this->auth_token = $data['auth_token'];
    	//$this->params = $data['params'];
	$this->setParams($data['params']);
    	$this->sign_type = $data['sign_type'];
    	$this->sign = $data['sign'];

	// 创建成功
	$this->status->setCode(ApiStatus::CODE_0);
        return $status;
    }

    /**
     * 接收一个请求
     * @return \app\common\Status
     */
    public function receive(){
        $jsonStr = file_get_contents("php://input");
        $jsonStr = strlen($jsonStr) > 0 ? $jsonStr : json_encode($_POST);
        return $this->create($jsonStr);
    }

    /**
     * 将当前请求对象，转换成一个关联数组
     * @return array	关联数组
     */
    public function toArray(){
        return array(
                'appid' => $this->appid,
                'method' => $this->method,
                'timestamp' => $this->timestamp,
                'version' => $this->version,
                'params' => $this->params,
                'sign_type' => $this->sign_type,
                'sign' => $this->sign,
            );
    }

    public function toString(){
        return json_encode( $this->toArray() );
    }
    public function __toString() {
        $this->toString();
    }


}
