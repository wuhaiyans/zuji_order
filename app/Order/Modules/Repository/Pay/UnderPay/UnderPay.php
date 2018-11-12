<?php
namespace App\Order\Modules\Repository\Pay\UnderPay;

class UnderPay{


    // class名称
    private $className = '';


    private $componnet;

    public function __construct( array $params ) {

        // 业务类型
        $business_type = $params['business_type'];

        // 根据业务类型 获取实例化类名称
        $this->className = UnderPayStatus::getBusinessClassName($business_type);

        $this->componnet = $params;

    }

    /**
     * 计算该付款金额
     * return string
     */
    public function getPayAmount(){

        $className = $this->getClssName();

        $classObject = new $className($this->componnet);
        return $classObject->getPayAmount();

    }

    /**
     * 实现具体业务
     * @return bool true  false
     */
    public function execute(){

        $className = $this->getClssName();

        $classObject = new $className($this->componnet);
        return $classObject->execute();
    }

    /**
     * 获取对象 class名称
     */
    public function getClssName(){

        $className = '\App\Order\Modules\Repository\Pay\UnderPay\\' . $this->className;
        if(!class_exists($className) ){
            throw new \Exception("UnderPay没有此项业务");
        }

        return $className;
    }


    /**
     * 获取对象 class 对象
     */
    public function getClssObj(){

        $className = $this->getClssName();

        return new $className($this->componnet);
    }



}
