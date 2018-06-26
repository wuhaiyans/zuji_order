<?php
namespace App\Lib\Order\Order;


 class TestApi extends \App\Lib\Common\Api\ApiContext{
	 
	 private $params = [
        'position'=>"index-lunbo",
        'length'=>10
	 ];
	 
	 public function getMethod(): string {
		 return 'zuji.content.query';
	 }
	 public function getRequestParams(): array {
		 return $this->params;
	 }
	public function setResponseData(array $data):bool{
		parent::setResponseData($data);
		
		return true;
	}

 }
 