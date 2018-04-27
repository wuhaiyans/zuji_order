<?php
/**
 * API 响应对象
 * 定义了接口响应交互协议
 * 用于:
 * 1）接收响应；
 * 2）发送响应；
 * @access public
 * @author Liuhongxing <liuhongxing@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 */
class ApiResponse {
    
    /**
     * 状态对象
     * @var ApiStatus
     */
    private $status = null;
    
    private $code = ApiStatus::CODE_50000;
    private $msg = '';
    private $sub_code = '';
    private $sub_msg = '';
    private $data = '';
    private $sign = '';	// 数据签名
    
    /**
     * 构造函数，初始化当前响应对象
     * @param string json格式字符串
     * @return type
     */
    public function __construct($jsonStr='') {
        
        $status = new ApiStatus();
        $status->success();// 默认为成功状态
        $this->status = $status;
	if( $jsonStr == '' ){
	    return ;
	}
	//-+--------------------------------------------------------------------
	// | 解析响应数据
	//-+--------------------------------------------------------------------
        if(strlen($jsonStr)==0){
            $status->setCode(ApiStatus::CODE_10000)->setMsg('空响应');
            return ;
        }
        $data  = json_decode($jsonStr,true);
        if( !is_array($data) ){
            $status->setCode(ApiStatus::CODE_10001)->setMsg('非json格式');
            return ;
        }
        
	//-+--------------------------------------------------------------------
	// | 校验响应参数
	//-+--------------------------------------------------------------------
        // code 参数
        if( !isset($data['code']) ){
            $status->setCode(ApiStatus::CODE_10002)->setMsg('code参数缺失');
            return ;
        }
        // sub_code 参数
        if( !isset($data['sub_code']) ){
            $status->setCode(ApiStatus::CODE_10003)->setMsg('sub_code参数缺失');
            return ;
        }
        // data 参数
        if( !isset($data['data']) || !is_string($data['data']) ){
            $status->setCode(ApiStatus::CODE_10004)->setMsg('data参数缺失或不是字符串');
            return ;
        }
        // sign 参数
        if( !isset($data['sign']) || !is_string($data['sign']) ){
            $status->setCode(ApiStatus::CODE_10005)->setMsg('sign参数缺失或不是字符串');
            return ;
        }
        
	//-+--------------------------------------------------------------------
	// | 数字验签
	//-+--------------------------------------------------------------------
        if( !ApiUtil::verify($data['data'], $data['sign'], 'ZUJI-PUBLIC-KEY') ){
            $status->setCode(ApiStatus::CODE_10006)->setMsg('签名错误');
            return ;
        }
	
        // msg 参数（可选）
        if( !isset($data['msg']) ){
	    $data['msg'] = '';
        }
        // sub_msg 参数（可选）
        if( !isset($data['sub_msg']) ){
	    $data['sub_msg'] = '';
        }
	
	//-+--------------------------------------------------------------------
	// | 赋值，初始化响应对象
	//-+--------------------------------------------------------------------
        $this->code = $data['code'];
        $this->msg = $data['msg'];
        $this->sub_code = $data['sub_code'];
        $this->sub_msg = $data['sub_msg'];
    	$this->data = $data['data'];
    	$this->sign = $data['sign'];
	
	// 创建成功
	$this->status->setCode(ApiStatus::CODE_0);
    }
    
    /**
     * 设置错误码
     * @param string $code  错误码
     */
    public function setCode( $code ){
	$this->code = $code;
	return $this;
    }
    public function setMsg( $msg ){
	$this->msg = $msg;
	return $this;
    }
    public function setSubCode( $subcCode ){
	$this->sub_code = $subcCode;
	return $this;
    }
    public function setSubMsg( $sub_msg ){
	$this->sub_msg = $sub_msg;
	return $this;
    }
    
    /**
     * 设置响应业务参数
     * @param mixed $data   【必须】返回业务参数，字符串必须是json格式；数组必须是关联数组
     */
    public function setData( $data ){
        if(!$data){
            $this->data = ['_'=>''];
            return $this;
        }
        if( is_string($data) ){
            $data = json_decode($data,true);
        }
        if( !is_array($data) ){
            exit('ApiResponse::setData() error');
        }
        $this->data = $data;
        return $this;
    }

    /**
     * 设置签名参数
     */
    public function setSign(){
        return $this->sign = $this->generateSign();
    }
    
    /**
     * @return array
     */
    public function getData(){
        return $this->data;
    }
    
    /**
     * 判断接口响应是否正确
     * @return boolean
     */
    public function isSuccessed(){
        return $this->status->isSuccess();
    }
    /**
     * 
     * @return ApiStatus
     */
    public function getStatus(){
        return $this->status;
    }
    
    public function flush(){
	echo $this->toString();
    }

    public function toArray() {
        return array(
            'code' => $this->code,
            'msg' => $this->msg,
            'sub_code' => $this->sub_code,
            'sub_msg' => $this->sub_msg,
            'data' => $this->data,
            'sign' => $this->sign,
        );
    }
    public function toString() {
        return json_encode($this->toArray());
    }
    public function __toString() {
        $this->toString();
    }

    /**
     * 生成签名签名
     * @return string
     */
    public function generateSign(){
        $request = api_request();
        $appid = $request->getAppid();
        $Redis = \zuji\cache\Redis::getInstans();
        $info = $Redis->hget('channel:appid', $appid);
        $appid_arr = json_decode($info, true);
        $rsa_encrypt = new \zuji\certificate\rsa\RSAEncrypter(1024, $appid_arr['client_public_key'], $appid_arr['platform_private_key']);
        $sign = $rsa_encrypt->sign(json_encode($this->getData()));
        return $sign;
    }
    
}
