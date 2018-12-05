<?php
namespace App\Lib\Alipay\Notary;

/**
 * 蚂蚁金服 金融科技 可信存证 异常封装
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 * @copyright (c) 2018, Huishoubao
 */

/**
 * 可信存证 异常封装
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class NotaryException extends \Exception {
	
	private $data;
	
	public function __construct( array $data=null ) {
		$message = '存证异常';
		
		$this->data = $data;
		
		if( isset($data['responseData']) ){
			$message = $data['responseData'];
		}
		parent::__construct( $message );
	}
	
	/**
	 * 获取错误提示
	 * @return string 错误提示
	 */
	public function getErrorMessage():string{
		return parent::getMessage();
	}
	
	/**
	 * 获取错误编码
	 * @return string 错误编码
	 */
	public function getErrorCode():string{
		return isset($this->data['code'])?$this->data['code']:'Error';
	}
	
	
	
}
